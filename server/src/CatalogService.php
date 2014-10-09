<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BriefSearchResult;

class CatalogService extends AbstractPrimoService
{

    public function searchCatalog($keyword)
    {
        $query = $this->getQuery($keyword);
        $result = $this->primo->search($query);
        return $this->buildCatalogResponse($result, $keyword);
    }

    /**
     * @param $keyword
     * @return mixed
     */
    protected function getQuery($keyword)
    {
        $query = $this->query_builder->keyword($keyword)->getQuery()
            ->local('BCL')->bulkSize(10);
        return $query;
    }

    protected function buildResponse(BriefSearchResult $result, $keyword)
    {
        $response = new \stdClass();

        $response->total_results = $result->total_results;
        $response->search_link = $this->searchCatalogDeepLink($keyword);

        $items = [];
        foreach ($result->results as $result) {
            $deep_link = $this->primo->createDeepLink();
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

    protected function searchCatalogDeepLink($keyword)
    {
        return 'http://bc-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/dlSearch.do?' .
        'institution=BCL&vid=bclib&onCampus=true&group=GUEST&loc=local,scope:(BCL)&query=any,contains,' .
        $keyword;
    }
}