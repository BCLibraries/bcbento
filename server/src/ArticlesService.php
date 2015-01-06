<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BibRecord;
use BCLib\PrimoServices\BriefSearchResult;

class ArticlesService extends AbstractPrimoService
{
    private $results_to_send = 5;

    private $type_map = [
        'book'                => 'Book',
        'video'               => 'Video',
        'journal'             => 'Journal',
        'government_document' => 'Government document',
        'database'            => 'Database',
        'image'               => 'Image',
        'audio_music'         => 'Musical recording',
        'article'             => 'Article',
        'newspaper_article'   => 'Newspaper article',
        'review'              => 'Review',
        'other'               => ''
    ];

    public function getQuery($keyword)
    {
        return $this->query_builder->keyword($keyword)->getQuery()
            ->articles()->bulkSize($this->results_to_send * 4);
    }

    protected function buildResponse(BriefSearchResult $result, $keyword)
    {
        $response = new \stdClass();

        $response->total_results = $result->total_results;
        $response->search_link = $this->searchArticlesDeepLink($keyword);

        $response_array = [];

        $current_result = 0;

        foreach ($result->results as $result) {

            // Skip non-fulltext records
            if ($result->field('delivery/fulltext') === 'no_fulltext') {
                continue;
            }

            $id_array = $result->field('search/recordid');
            $id = isset($id_array) ? $id_array : '';

            $deep_link = 'http://bc-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/dlDisplay.do?';
            $deep_link .= 'vid=bclib&loc=adaptor%2Cprimo_central_multiple_fe';
            $deep_link .= '&docId=' . $result->id;

            $response_array[] = [
                'id'        => $id,
                'title'     => $result->title,
                'date'      => $result->date,
                'publisher' => $result->publisher,
                'creator'   => $result->field('display/creator'),
                'link'      => $deep_link,
                'source'    => $result->field('display/source'),
                'part_of'   => $result->field('display/ispartof'),
                'type'      => $this->displayType($result),
                'real_type' => $result->type
            ];

            if ($current_result >= $this->results_to_send) {
                break;
            }

            $current_result++;

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