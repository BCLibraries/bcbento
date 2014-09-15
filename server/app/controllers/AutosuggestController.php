<?php

use Doctrine\Common\Cache\Cache;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class AutosuggestController extends BaseController
{

    /**
     * @var ElasticSearch\Client
     */
    private $_elastic_search;
    /**
     * @var Illuminate\Support\Facades\Response
     */
    private $_response;
    /**
     * @var Illuminate\Http\Request
     */
    private $_request;
    /**
     * @var Doctrine\Common\Cache\Cache
     */
    private $_cache;

    public function __construct(
        Elasticsearch\Client $elastic_search,
        Response $response,
        Request $request,
        Cache $cache
    )
    {
        $this->_elastic_search = $elastic_search;
        $this->_response = $response;
        $this->_request = $request;
        $this->_cache = $cache;
    }

    public function suggest($term = '')
    {
        $input = $term ? $term : $this->_request->get('text');

        $cache_key = $this->_typeaheadKey($input);

        if ($this->_cache->contains($cache_key)) {
            $result = $this->_cache->fetch($cache_key);
        } else {
            $result = $this->_fetchSuggestions($input);
            $this->_cache->save($cache_key, $result, 86400);
        }

        return $this->_response->json($result)->setCallback(Input::get('callback'));
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
        return 'typeahead-key:' . $text;
    }
}