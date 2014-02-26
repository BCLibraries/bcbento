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

    private $_keyword;

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
        $this->_keyword = $this->_request->get('any');
        $query = $this->_query_builder->keyword($this->_keyword)->getQuery()
            ->bulkSize(5);
        $result = $this->_primo->search($query);
        $response = $this->_buildCatalogResponse($result);
        return $this->_response->json($response);
    }

    public function articles()
    {
        $this->_keyword = $this->_request->get('any');
        $query = $this->_query_builder->keyword($this->_keyword)->getQuery()
            ->articles()->bulkSize(4);
        $result = $this->_primo->search($query);
        $response_array = $this->_buildArticleResponse($result);
        return $this->_response->json($response_array);
    }

    protected function _buildCatalogResponse(\BCLib\PrimoServices\BriefSearchResult $result)
    {
        $response = new stdClass();

        $response->total_results = $result->total_results;
        $response->search_link = $this->_searchDeepLink();

        $items = [];
        foreach ($result->results as $result) {
            $deep_link = $this->_primo->createDeepLink();
            $items[] = [
                'id'           => $result->id,
                'title'        => $result->title,
                'date'         => $result->date,
                'publisher'    => $result->publisher,
                'creator'      => $result->creator->display_name,
                'contributors' => $result->contributors,
                'link'         => $deep_link->link($result->id)
            ];

        }
        $response->items = $items;
        return $response;
    }


    protected function _buildArticleResponse(\BCLib\PrimoServices\BriefSearchResult $result)
    {
        $response_array = [];
        foreach ($result->results as $result) {
            $id_array = $result->field('//prim:search/prim:recordid');
            $id = isset($id_array[0]) ? $id_array[0] : '';

            $deep_link = 'http://bc-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/dlDisplay.do?';
            $deep_link .= 'vid=bclib&loc=adaptor%2Cprimo_central_multiple_fe';
            $deep_link .= '&docId=' . $result->id;

            $response_array[] = [
                'id'        => $id,
                'title'     => $result->title,
                'date'      => $result->date,
                'publisher' => $result->publisher,
                'creator'   => $result->field('//prim:display/prim:creator'),
                'link'      => $deep_link,
                'source'    => $result->field('//prim:display/prim:source'),
                'part_of'   => $result->field('//prim:display/prim:ispartof')
            ];

        }
        return $response_array;
    }

    protected function _searchDeepLink()
    {
        return 'http://bc-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/dlSearch.do?' .
        'institution=BCL&vid=bclib&onCampus=true&group=GUEST&loc=local,scope:(BCL)&query=any,contains,' .
        $this->_keyword;
    }
}
