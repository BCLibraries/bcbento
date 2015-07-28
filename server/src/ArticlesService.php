<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BibRecord;
use BCLib\PrimoServices\BriefSearchResult;
use BCLib\PrimoServices\QueryTerm;

class ArticlesService extends AbstractPrimoService
{
    private $results_to_send = 3;
    private $current_article;

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
        'reference_entry'     => 'Reference entry',
        'other'               => ''
    ];

    public function getQuery($keyword)
    {
        $term = new QueryTerm();
        $term->set('facet_tlevel','exact','online_resources_PC_TN');
        return $this->query_builder->keyword($keyword)->getQuery()->addTerm($term)
            ->articles()->bulkSize($this->results_to_send)->onCampus(true);
    }

    protected function buildResponse(BriefSearchResult $result, $keyword)
    {
        $response = new \stdClass();
        $this->current_article = 0;
        $response->total_results = $result->total_results;
        $response->search_link = $this->searchArticlesDeepLink($keyword);
        $response->items = array_map([$this, 'buildItem'], $result->results);
        return $response;
    }

    protected function searchArticlesDeepLink($keyword)
    {
        return 'http://bc-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/dlSearch.do?' .
        'institution=BCL&vid=bclib&onCampus=false&group=GUEST&tab=pci_only&query=any,contains,' .
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

    protected function buildItem(BibRecord $result)
    {
        $id_array = $result->field('search/recordid');
        $id = isset($id_array) ? $id_array : '';

        $deep_link = 'http://bc-primo.hosted.exlibrisgroup.com/primo_library/libweb/action/dlDisplay.do?';
        $deep_link .= 'vid=bclib&loc=adaptor%2Cprimo_central_multiple_fe';
        $deep_link .= '&docId=' . $result->id;

        return [
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
    }
}