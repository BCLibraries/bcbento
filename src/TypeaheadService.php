<?php

namespace BCLib\BCBento;

use Elasticsearch\Client;

class TypeaheadService implements ServiceInterface
{

    /**
     * @var Client
     */
    private $_elastic_search;

    public function __construct(
        Client $elastic_search = null
    ) {
        $this->_elastic_search = $elastic_search;
    }

    public function fetch($input)
    {
        return $this->_fetchSuggestions($input);
    }

    public function _fetchSuggestions($input)
    {
        $params = [
            'index' => 'autocomplete',
            'body'  => [
                'ac' => [
                    'text'       => $input,
                    'completion' => [
                        'field' => 'suggest'
                    ]
                ]
            ]
        ];

        $suggestions = $this->_elastic_search->suggest($params);

        $results = [];

        if (!isset($suggestions['ac'][0])) {
            return $results;
        }

        foreach ($suggestions['ac'][0]['options'] as $term) {
            $results[] = [
                'value' => rtrim($term['text'], ' ,:/\\.'),
                'type'  => '',
                'all'   => $term
            ];
        }
        return $results;
    }
}