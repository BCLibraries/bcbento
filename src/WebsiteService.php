<?php

namespace BCLib\BCBento;

use Elasticsearch\Client;

class WebsiteService implements ServiceInterface
{
    /**
     * @var Client
     */
    private $elastic_search;

    /**
     * @var
     */
    private $es_version;

    public function __construct(Client $elastic_search, $es_version)
    {
        $this->elastic_search = $elastic_search;
        $this->es_version = $es_version;
    }

    public function fetch($keyword)
    {
        $params = [
            'index' => 'website',
            'body'  => [
                'query'     => [
                    'multi_match' => [
                        'query'  => $keyword,
                        'fields'   => [
                            'title^5',
                            'title.english^5',
                            'guide_title^6',
                            'guide_title.english^6',
                            'guide_subjects^4',
                            'guide_subjects.english^4',
                            'guide_tags^4',
                            'guide_tags.english^4',
                            'url^5',
                            'guide_url^6',
                            'guide_description^6',
                            'guide_description.english^6',
                            'text^1'
                        ],
                        'operator' => 'and'
                    ]
                ],
                'from'      => 0,
                'size'      => 3,
                'highlight' => [
                    'fields' =>
                        ['text' => (object) [
                            'fragment_size' => 150
                        ]]
                ]
            ]
        ];
        $response = $this->elastic_search->search($params);
        return $this->buildResponse($response, $keyword);
    }

    private function buildResponse($json_response, $keyword)
    {
        $search_url = "http://libguides.bc.edu/srch.php?q=$keyword";
        $items = array_map([$this, 'buildItem'], $json_response['hits']['hits']);
        $response = new SearchResponse($items, $search_url, $json_response['hits']['total']);
        return $response;
    }

    private function buildItem($hit_json)
    {
        $item = [
            'page_title'  => $hit_json['_source']['title'],
            'guide_title' => $hit_json['_source']['guide_title'],
            'guide_id'    => $hit_json['_source']['guide_id'],
            'url'         => $hit_json['_source']['url'],
            'guide_url'   => $hit_json['_source']['guide_url'],
            'updated'     => $hit_json['_source']['updated'],
            'highlight'   => isset($hit_json['highlight']) ? $hit_json['highlight']['text'] : []
        ];
        $item['title'] = $this->buildTitle($item['guide_title'], $item['page_title']);
        return $item;
    }

    private function buildTitle($guide_title, $page_title)
    {
        return $page_title === 'Home' ? $guide_title : "$guide_title : $page_title";
    }
}