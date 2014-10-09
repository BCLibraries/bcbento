<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BriefSearchResult;

class ArticlesService extends AbstractPrimoService
{
    public function getQuery($keyword)
    {
        return $this->query_builder->keyword($keyword)->getQuery()
            ->articles()->bulkSize(3);
    }

    protected function buildResponse(BriefSearchResult $result, $keyword)
    {
        $response = new \stdClass();

        $response->total_results = $result->total_results;
        $response->search_link = $this->searchArticlesDeepLink($keyword);

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
        $response->items = $response_array;
        return $response;
    }

    protected function searchArticlesDeepLink($keyword)
    {
        return 'http://bc-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/dlSearch.do?' .
        'institution=BCL&vid=bclib&onCampus=true&group=GUEST&tab=pci_only&query=any,contains,' .
        $keyword . '&loc=adaptor%2Cprimo_central_multiple_fe';
    }

}