<?php

namespace BCLib\BCBento;

use Doctrine\Common\Cache\CacheProvider;
use Elasticsearch\Client;

class Typeahead implements ServiceInterface
{

    /**
     * @var Client
     */
    private $_elastic_search;

    /**
     * @var CacheProvider
     */
    private $_cache;

    public function __construct(
        Client $elastic_search = null,
        CacheProvider $cache = null
    ) {
        $this->_elastic_search = $elastic_search;
        $this->_cache = $cache;
    }

    public function fetch($input)
    {
        $cache_key = $this->_typeaheadKey($input);

        if ($this->_cache->contains($cache_key)) {
            $result = $this->_cache->fetch($cache_key);
        } else {
            $result = $this->_fetchSuggestions($input);
            $this->_cache->save($cache_key, $result, 86400);
        }

        return $result;
    }

    public function _fetchSuggestions($input)
    {
        $params = [
            'index' => 'autocomp',
            'body' => [
                'ac' => [
                    'text' => $input,
                    'completion' => [
                        'field' => 'name_suggest'
                    ]
                ]
            ]
        ];

        return $this->_elastic_search->suggest($params);
    }

    private function _typeaheadKey($text)
    {
        return 'typeahead-key:' . sha1($text);
    }
}