<?php

use \Illuminate\Support\Facades\Response;
use \Illuminate\Http\Request;
use TFN\DPLA;

class DPLAController extends BaseController
{

    /**
     * @var TFN\DPLA
     */
    private $_dpla;
    /**
     * @var Illuminate\Http\Request
     */
    private $_request;
    /**
     * @var Illuminate\Support\Facades\Response
     */
    private $_response;

    public function __construct(DPLA $dpla, Request $request, Response $response)
    {
        $this->_dpla = $dpla;
        $this->_request = $request;
        $this->_response = $response;
    }

    public function search()
    {
        $result = $this->_dpla->createSearchQuery()->forText($this->_request->get('any'))
            ->withPaging(1, 4)->execute();
        $response = new stdClass();
        $response->total = $result->getTotalCount();
        $response->link = 'http://dp.la/search?utf8=%E2%9C%93&q=' . $this->_request->get('any');
        return $this->_response->json($response)->setCallback(Input::get('callback'));
    }
} 