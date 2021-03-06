<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BibRecord;
use BCLib\PrimoServices\BriefSearchResult;
use BCLib\PrimoServices\QueryTerm;

class ArticlesService extends AbstractPrimoService
{
    private $results_to_send = 3;

    public function getQuery($keyword): \BCLib\PrimoServices\Query
    {
        $term = new QueryTerm();
        $term->set('facet_tlevel', 'exact', 'online_resources_PC_TN');
        return $this->query_builder->keyword($keyword)->getQuery()->addTerm($term)
            ->articles()->bulkSize($this->results_to_send)->onCampus();
    }

    protected function buildResponse(BriefSearchResult $result, $keyword)
    {
        $items = array_map([$this, 'buildItem'], $result->results);
        return new SearchResponse($items, $this->searchPermalink($keyword, true), $result->total_results);
    }

    protected function buildItem(BibRecord $result)
    {
        $id_array = $result->field('search/recordid');
        $id = $id_array ?? '';

        return [
            'id'        => $id,
            'title'     => $this->stripTags($result->title),
            'date'      => $this->stripTags($result->date),
            'publisher' => $this->stripTags($result->publisher),
            'creator'   => $this->stripTags($result->field('display/creator')),
            'link'      => $this->itemPermalink($result, true),
            'source'    => $this->stripTags($result->field('display/source')),
            'part_of'   => $this->stripTags($result->field('display/ispartof')),
            'journal'   => $this->stripTags($result->field('addata/jtitle')),
            'type'      => $this->stripTags($this->displayType($result)),
            'real_type' => $result->type
        ];
    }

    protected function stripTags($field)
    {
        if (\is_array($field)) {
            $result = array();
            foreach ($field as $member) {
                $result[] = strip_tags($member);
            }
        } else {
            $result = strip_tags($field);
        }
        return $result;
    }
}