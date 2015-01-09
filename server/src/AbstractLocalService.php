<?php

namespace BCLib\BCBento;

use Doctrine\Common\Cache\Cache;
use Elasticsearch\Client;

abstract class AbstractLocalService implements ServiceInterface
{
    /**
     * @var \ElasticSearch\Client
     */
    protected $elastic_search;

    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    protected $cache;

    protected $index;

    public function __construct(Client $elastic_search, Cache $cache)
    {
        $this->elastic_search = $elastic_search;
        $this->cache = $cache;
    }

    public function fetch($keyword)
    {
        $terms_response = $this->getRelevantTerms($keyword);
        return $this->getResult($keyword, $terms_response);
    }

    public function getResult($keyword, array $terms_response)
    {
        $params = [
            'index' => $this->index,
            'body'  => $this->buildQuery($keyword, $terms_response)
        ];
        $librarians = $this->elastic_search->search($params);
        return $this->buildResponse($librarians);
    }

    abstract public function buildQuery($keyword, array $terms_response);

    abstract public function buildResponse(array $terms_response);

    protected function getRelevantTerms($keyword)
    {
        $cache_key = $this->cacheKey($keyword);
        if ($this->cache->contains($cache_key)) {
            return $this->cache->fetch($cache_key);
        }
        $params = [];
        $params['index'] = 'records';
        $params['body'] = [
            'query'  => [
                'query_string' => [
                    'query'            => $keyword,
                    'default_operator' => 'AND',
                    'fields'           => [
                        "title^10",
                        "author^5",
                        "subject^3",
                        "description",
                        "issn",
                        "isbn"
                    ],
                    'use_dis_max'      => false
                ]
            ],
            'from'   => 0,
            'size'   => 0,
            'sort'   => [],
            'facets' => [
                'LCCDep1' => [
                    'terms_stats' => [
                        'key_field'    => 'LCCDep1',
                        'value_script' => 'doc.score'
                    ]
                ],
                'LCCDep2' => [
                    'terms_stats' => [
                        'key_field'    => 'LCCDep2',
                        'value_script' => 'doc.score'
                    ]
                ],
                'LCCDep3' => [
                    'terms_stats' => [
                        'key_field'    => 'LCCDep3',
                        'value_script' => 'doc.score'
                    ]
                ]
            ]
        ];

        $response = $this->elastic_search->search($params);

        $facet_array = [];
        foreach ($response['facets'] as $facet) {
            $facet_array[] = $facet['terms'];
        }

        $this->cache->save($cache_key, $facet_array, 60 * 60 * 24);

        return $facet_array;
    }

    protected function cacheKey($term)
    {
        return 'search-terms:' . sha1($term);
    }
}