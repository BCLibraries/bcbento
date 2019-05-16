<?php

namespace BCLib\BCBento;

use Elasticsearch\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class AbstractLocalService implements ServiceInterface
{
    /**
     * @var \ElasticSearch\Client
     */
    protected $elastic_search;

    protected $index;

    protected $es_version;

    public function __construct(Client $elastic_search, $es_version)
    {
        $this->elastic_search = $elastic_search;
        $this->es_version = $es_version;
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
        $keyword = str_replace(':', '', $keyword);

        $score_script = ($this->es_version < '1.3.2') ? 'doc.score' : '_score';

        $params = [];
        $params['index'] = 'catalog';
        $params['body'] = [
            'query'  => [
                'query_string' => [
                    'query'            => $keyword,
                    'default_operator' => 'AND',
                    'fields'           => [
                        'title^10',
                        'author^5',
                        'subjects^3',
                        'description',
                        'toc',
                        'issn',
                        'isbn'
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
                        'key_field'    => 'tax1',
                        'value_script' => $score_script
                    ]
                ],
                'LCCDep2' => [
                    'terms_stats' => [
                        'key_field'    => 'tax2',
                        'value_script' => $score_script
                    ]
                ],
                'LCCDep3' => [
                    'terms_stats' => [
                        'key_field'    => 'tax3',
                        'value_script' => $score_script
                    ]
                ]
            ]
        ];

        $facet_array = [];

        $response = $this->elastic_search->search($params);

        foreach ($response['facets'] as $facet) {
            if (\count($facet['terms'])) {
                $facet_array[] = $facet['terms'];
            }
        }


        return $facet_array;
    }

    protected function cacheKey($term)
    {
        return 'search-terms:'.sha1($term);
    }

    protected function logQuery()
    {
        $logger = new Logger('eslog');
        $logger->pushHandler(new StreamHandler('/usr/local/var/log/bcbento/bcbento.log', Logger::INFO));


        $dump = $this->elastic_search->transport->getConnection()->getLastRequestInfo()['request']['body'];
        $logger->info($dump);
    }
}
