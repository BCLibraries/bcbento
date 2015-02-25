<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\Availability\ClientFactory;
use BCLib\PrimoServices\BibRecord;
use BCLib\PrimoServices\BriefSearchResult;
use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\QueryBuilder;

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

    /**
     * @var WorldCatService
     */
    private $worldcat;

    public function __construct(PrimoServices $primo, QueryBuilder $query_builder, WorldCatService $worldcat)
    {
        parent::__construct($primo, $query_builder);
        $this->worldcat = $worldcat;
    }

    /**
     * @param $keyword
     * @return mixed
     */
    protected function getQuery($keyword)
    {
        $query = $this->query_builder->keyword($keyword)->getQuery()
            ->local('BCL')->bulkSize(6);
        return $query;
    }

    protected function buildResponse(BriefSearchResult $result, $keyword)
    {
        if ($result->total_results == 0) {
            return $this->worldcat->fetch($keyword);
        }

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

        $item->cover_images = array_map([$this, 'getMediumCoverImage'], $item->cover_images);
        $item->cover_images = array_filter($item->cover_images, [$this, 'removeAmazonCoverImages']);
        $item->cover_images = array_values($item->cover_images);

        $date = $item->field('addata/date');
        $date = is_array($date) ? $date[0] : $date;

        return [
            'id'           => $item->id,
            'title'        => $item->title,
            'date'         => $date,
            'publisher'    => $item->publisher,
            'creator'      => $item->creator->display_name,
            'contributors' => $item->contributors,
            'link'         => "http://" . $this->primo->createDeepLink()->link($item->id),
            'link_to_rsrc' => $this->buildLinksToResource($item),
            'covers'       => $item->cover_images,
            'isbn'         => $item->isbn,
            'type'         => $this->displayType($item),
            'avail'        => $this->buildAvailability($item->components),
            'toc'          => $this->tableOfContents($item)
        ];
    }

    /**
     * @param $components \BCLib\PrimoServices\BibComponent[]
     * @return array
     */
    protected function buildAvailability(array $components)
    {
        $availabilities = [];

        foreach ($components as $comp) {
            foreach ($comp->availability as $avail) {
                $availabilities[] = $avail;
            }
        }

        return $availabilities;
    }

    /**
     * Replace small Syndetics thumbs with medium-sized
     *
     * @param $image_url
     * @return string
     */
    private function getMediumCoverImage($image_url)
    {
        return str_replace('/SC.JPG', '/MC.JPG', $image_url);
    }

    /**
     * Don't use Amazon images
     *
     * @param $image_url
     * @return bool
     */
    private function removeAmazonCoverImages($image_url)
    {
        return (!strpos($image_url, 'amazon.com'));
    }

    private function buildLinksToResource(BibRecord $item)
    {
        $response = [];

        $link_to_rsrc = $item->field('links/linktorsrc') ? $item->field('links/linktorsrc') : [];
        $link_to_rsrc = is_array($link_to_rsrc) ? $link_to_rsrc : [$link_to_rsrc];

        foreach ($link_to_rsrc as $link) {
            list($url, $text) = explode('$$D', $link);
            $url = str_replace('$$U', '', $url);
            $response[] = [
                'url'  => $url,
                'text' => $text
            ];
        }

        return $response;
    }
}