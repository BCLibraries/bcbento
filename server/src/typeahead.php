<?php

namespace BCLib\BCBento;

use Elasticsearch\Client;

class Typeahead implements ServiceInterface
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
            'index' => 'autocomp',
            'body'  => [
                'ac' => [
                    'text'       => $input,
                    'completion' => [
                        'field' => 'name_suggest'
                    ]
                ]
            ]
        ];

        return $this->_elastic_search->suggest($params);
    }
}