<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\Availability\ClientFactory;
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
        'data'                => 'Data',
        'other'               => ''
    ];

    public function searchCatalog($keyword)
    {
        $query = $this->getQuery($keyword);
        $result = $this->primo->search($query);
        return $this->buildResponse($result, $keyword);
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

        $client_factory = new ClientFactory();
        $rta = $client_factory->buildAlmaClient('alma.exlibrisgroup.com', '01BC_INST');
        $rta->checkAvailability($result->results);
        $response->items = array_map([$this, 'buildItem'], $result->results);

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

    protected function tableOfContents(BibRecord $item)
    {
        $toc_out = [];
        $source_toc = $item->field('search/toc');

        if (!is_array($source_toc)) {
            $source_toc = [$source_toc];
        }

        foreach ($source_toc as $field505) {
            $parts = explode(' -- ', $field505);
            $toc_out = array_merge($toc_out, $parts);
        }

        return $toc_out;
    }

    protected function buildItem(BibRecord $item)
    {
        if (empty($item->cover_images)) {
            $item->cover_images = [''];
        }

        return [
            'id'           => $item->id,
            'title'        => $item->title,
            'date'         => $item->date,
            'publisher'    => $item->publisher,
            'creator'      => $item->creator->display_name,
            'contributors' => $item->contributors,
            'link'         => $this->primo->createDeepLink()->link($item->id),
            'covers'       => $item->cover_images,
            'isbn'         => $item->isbn,
            'type'         => $this->displayType($item),
            'avail'        => $this->buildAvailability($item->components),
            'toc'          => $this->tableOfContents($item)
        ];
    }

    protected function buildAvailability($components)
    {
        $availabilities = [];

        foreach ($components as $comp) {
            foreach ($comp->availability as $avail) {
                $availabilities[] = $avail;
            }
        }

        return $availabilities;
    }
}