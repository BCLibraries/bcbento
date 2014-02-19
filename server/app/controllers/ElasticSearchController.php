<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;

class ElasticSearchController extends BaseController
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

    public function __construct(
        Elasticsearch\Client $elastic_search,
        Response $response,
        Request $request
    ) {
        $this->_elastic_search = $elastic_search;
        $this->_response = $response;
        $this->_request = $request;
    }

    public function suggest()
    {
        $input = $this->_request->get('text');
        $params = [];
        $params['index'] = 'autocomp';
        $params['body'] = [
            'ac' => [
                'text'       => $input,
                'completion' => [
                    'field' => 'name_suggest'
                ]
            ]
        ];

        $result = $this->_elastic_search->suggest($params);
        return $this->_response->json($result);
    }

    public function guides()
    {
        $input = $this->_request->get('text');
        $params = [];
        $params['index'] = 'records_v0';
        $params['body'] = '{
   "query": {
      "query_string": {
         "query": "' . $input . '"
      }
   },"facets": {
      "LCCDep1": {
         "terms": {
            "field": "LCCDep1"
         }
      },
      "LCCDep2": {
         "terms": {
            "field": "LCCDep2"
         }
      }
   }

   }';
        return $this->_response->json($this->_elastic_search->search($params));
    }
}