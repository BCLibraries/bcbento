<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BibRecord;
use BCLib\PrimoServices\BriefSearchResult;

class CatalogService extends AbstractPrimoService
{

    private $type_map = [
        'book'                => 'Book',
        'video'               => 'Video',
        'journal'             => 'Journal',
        'government_document' => 'Government document',
        'database'            => 'Database',
        'image'               => 'Image',
        'audio_music'         => 'Musical recording',
        'realia'              => '',
        'other'               => ''
    ];

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
        foreach ($result->results as $item) {
            $deep_link = $this->primo->createDeepLink();

            if (empty($item->cover_images)) {
                $item->cover_images = [''];
            }

            $type = $this->displayType($item);

            $items[] = [
                'id'           => $item->id,
                'title'        => $item->title,
                'date'         => $item->date,
                'publisher'    => $item->publisher,
                'creator'      => $item->creator->display_name,
                'contributors' => $item->contributors,
                'link'         => $deep_link->link($item->id),
                'covers'       => $item->cover_images,
                'isbn'         => $item->isbn,
                'type'         => $type
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

    protected function displayType(BibRecord $item)
    {


        if (isset($this->type_map[$item->type])) {
            $display_type = $this->type_map[$item->type];
        } else {
            $display_type = $item->type;
        }
        return $display_type;
    }
}