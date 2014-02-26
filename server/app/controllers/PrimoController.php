<?php

use BCLib\PrimoServices\PrimoServices;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use BCLib\PrimoServices\QueryBuilder;

class PrimoController extends BaseController
{
    /**
     * @var BCLib\PrimoServices\PrimoServices
     */
    protected $_primo;

    /**
     * @var Illuminate\Support\Facades\Response
     */
    protected $_response;

    /**
     * @var Illuminate\Http\Request
     */
    private $_request;

    /**
     * @var BCLib\PrimoServices\QueryBuilder
     */
    private $_query_builder;

    public function __construct(
        PrimoServices $service,
        Response $response,
        Request $request,
        QueryBuilder $query_builder
    ) {
        $this->_primo = $service;
        $this->_response = $response;
        $this->_request = $request;
        $this->_query_builder = $query_builder;
    }

    public function catalog()
    {
        $query = $this->_query_builder->keyword($this->_request->get('any'))->getQuery()
            ->bulkSize(5);
        $result = $this->_primo->search($query);
        $response_array = $this->_buildCatalogResponse($result);
        return $this->_response->json($response_array);
    }

    public function articles()
    {
        $query = $this->_query_builder->keyword($this->_request->get('any'))->getQuery()
            ->articles()->bulkSize(4);
        $result = $this->_primo->search($query);
        $response_array = $this->_buildArticleResponse($result);
        return $this->_response->json($response_array);
    }

    protected function _buildCatalogResponse(\BCLib\PrimoServices\BriefSearchResult $result)
    {
        $response_array = [];
        foreach ($result->results as $result) {
            $deep_link = $this->_primo->createDeepLink();
            $response_array[] = [
                'id'           => $result->id,
                'title'        => $result->title,
                'date'         => $result->date,
                'publisher'    => $result->publisher,
                'creator'      => $result->creator->display_name,
                'contributors' => $result->contributors,
                'link'         => $deep_link->link($result->id)
            ];

        }
        return $response_array;
    }


    protected function _buildArticleResponse(\BCLib\PrimoServices\BriefSearchResult $result)
    {
        $response_array = [];
        foreach ($result->results as $result) {
            $deep_link = $this->_primo->createDeepLink();
            $id_array = $result->field('//prim:search/prim:recordid');
            $id = isset($id_array[0]) ? $id_array[0] : '';
            $response_array[] = [
                'id'        => $id,
                'title'     => $result->title,
                'date'      => $result->date,
                'publisher' => $result->publisher,
                'creator'   => $result->field('//prim:display/prim:creator'),
                'link'      => $deep_link->link($id),
                'source'    => $result->field('//prim:display/prim:source'),
                'part_of'   => $result->field('//prim:display/prim:ispartof')
            ];

        }
        return $response_array;
    }
}
