<?php

namespace BCLib\BCBento;

use Elasticsearch\Client;

abstract class AbstractLocalService implements ServiceInterface
{
    /**
     * @var \ElasticSearch\Client
     */
    protected $elastic_search;

    protected $index;

    public function __construct(Client $elastic_search)
    {
        $this->elastic_search = $elastic_search;
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
                        "subjects^3",
                        "description",
                        "issn",
                        "isbn"
                    ],
                    'use_dis_max'      => false
                ]
            ],
            'from'   => 0,
            'size'   => 10,
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
            if (count($facet['terms'])) {
                $facet_array[] = $facet['terms'];
            }
        }

        return $facet_array;
    }

    protected function cacheKey($term)
    {
        return 'search-terms:' . sha1($term);
    }
}