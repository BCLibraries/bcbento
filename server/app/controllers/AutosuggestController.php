<?php

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

        $result = $this->_elastic_search->suggest($params);

        return $this->_response->json($result)->setCallback(Input::get('callback'));
    }

}