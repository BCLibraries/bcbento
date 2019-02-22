<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\Availability\Availability;
use BCLib\PrimoServices\Availability\ClientFactory;
use BCLib\PrimoServices\BibRecord;
use BCLib\PrimoServices\BriefSearchResult;
use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\QueryBuilder;

class CatalogService extends AbstractPrimoService
{

    const LIB_MAP = [
        'ARCH'  => 'Burns Archives',
        'TML'   => 'Theology and Ministry Library',
        'ERC'   => 'Educational Resource Center',
        'ONL'   => 'O\'Neill Library',
        'BURNS' => 'Burns Library',
        'BAPST' => 'Bapst Library',
        'LAW'   => 'Law School Library'
    ];

    /**
     * @var WorldCatService
     */
    private $worldcat;

    const LIB_USE_ONLY = [
        'Reference No Loan',
        'Reading Room Use Only',
        'Reference Folio No Loan'
    ];

    public function __construct(PrimoServices $primo, QueryBuilder $query_builder, WorldCatService $worldcat)
    {
        parent::__construct($primo, $query_builder);
        $this->worldcat = $worldcat;
    }

    /**
     * @param $keyword
     * @return \BCLib\PrimoServices\Query
     */
    protected function getQuery($keyword): \BCLib\PrimoServices\Query
    {
        $query = $this->query_builder->keyword($keyword)->getQuery()
            ->local('BCL')->bulkSize(10)->dym();
        return $query;
    }

    protected function buildResponse(BriefSearchResult $result, $keyword)
    {
        try {
            if ($result->total_results === 0) {
                //return $this->worldcat->fetch($keyword);
            }
        } Catch (\Exception $e) {
            // Do nothing for now.
        }

        $client_factory = new ClientFactory();
        $rta = $client_factory->buildAlmaClient('bc.alma.exlibrisgroup.com', '01BC_INST');
        try {
            $rta->checkAvailability($result->results);
        } catch (\Exception $e) {
            // @TODO respond better to this
            $avail_error_log = '/apps/bcbento.versions/logs/avail-error.log';
            $timestamp = date('Y-m-d H:i:s');
            $message = "$timestamp {$e->getMessage()}\n";
            $components = iterator_to_array($rta->buildComponentsHash($result->results));
            $items = implode(':', array_keys($components));
            // error_log("$message ($items)", 3, $avail_error_log);
        }
        $items = array_map([$this, 'buildItem'], $result->results);

        $response = new SearchResponse($items, $this->searchPermalink($keyword), $result->total_results);
        $response->addField('dym', $result->dym);
        return $response;
    }

    protected function tableOfContents(BibRecord $item)
    {
        $toc_out = [[]];
        $source_toc = (array) $item->field('search/toc');
        foreach ($source_toc as $field505) {
            $toc_out[] = explode(' -- ', $field505);
        }
        return array_merge(...$toc_out);
    }

    protected function buildItem(BibRecord $item)
    {
        $display_type = $this->displayType($item);

        $item->cover_images = $this->coverImages($item);

        $date = $item->field('addata/date');
        $date = \is_array($date) ? $date[0] : $date;

        $availabilities = $this->buildAvailabilities($item->components);

        $getit = $this->getItLink($item);

        return [
            'id'           => $item->id,
            'title'        => $item->title,
            'date'         => $date,
            'publisher'    => $item->publisher,
            'creator'      => $item->creator->display_name,
            'contributors' => $item->contributors,
            'link'         => $this->itemPermalink($item),
            'link_to_rsrc' => [],
            'covers'       => $item->cover_images,
            'isbn'         => $item->isbn,
            'type'         => $display_type,
            'avail'        => $availabilities,
            'getit'        => $getit,
            'toc'          => $this->tableOfContents($item)
        ];
    }

    protected function buildAvailabilities(array $components)
    {
        $availabilities = [];

        foreach ($components as $comp) {
            foreach ($comp->availability as $avail) {
                $availabilities[] = $this->buildAvailability($avail);
            }
        }

        return $availabilities;
    }

    protected function buildAvailability(Availability $avail)
    {
        $avail_obj = new \stdClass();
        $avail_obj->availability = $avail->availability;
        $avail_obj->library = $avail->library;
        $avail_obj->call_number = $avail->call_number;
        $avail_obj->on_shelf = ($avail->availability === 'available' || $avail->availability === 'check_holdings');
        $avail_obj->check_avail = ($avail->availability === 'check_holdings');
        $avail_obj->in_library_only = \in_array($avail->location, self::LIB_USE_ONLY, true);
        $avail_obj->lib_display = $this->libraryDisplayValue($avail);
        $avail_obj->loc_display = $avail->location;
        $avail_obj->full = $avail;
        return $avail_obj;
    }

    protected function getItLink(BibRecord $item)
    {
        $getit = false;
        $i = 0;
        while (isset($item->getit[$i]) && !$getit) {
            if ($item->getit[$i]->category === 'Alma-E') {
                $getit = $item->getit[$i]->getit_1;
            } else {
                if ($item->getit[$i]->category === 'Online Resource') {
                    $getit = $item->getit[$i]->getit_1;
                }
            }
            $i++;
        }
        return $getit;
    }

    protected function buildTabParameter($getit, array $availabilities)
    {
        foreach ($availabilities as $avail) {
            if (strpos($avail->library, 'BURNS') !== false || strpos($avail->library, 'ARCH') !== false) {
                return '';
            }
        }
        return $getit ? '"&tabs=viewOnlineTab' : '"&tabs=requestTab';
    }

    protected function libraryDisplayValue(Availability $avail): string
    {
        $lib_is_set = isset($avail->library, self::LIB_MAP[$avail->library]);
        return $lib_is_set ? self::LIB_MAP[$avail->library] : $avail->library;
    }

    /**
     * @param BibRecord $item
     * @return array
     */
    protected function coverImages(BibRecord $item): array
    {
        $cover_images = $item->cover_images;

        if (empty($cover_images)) {
            return [false];
        }

        if ($item->type === 'collection') {
            $url_base = 'http://bc.alma.exlibrisgroup.com/view/delivery/thumbnail/01BC_INST';
            $bare_id = str_replace('ALMA-BC','',$item->id);
            return ["$url_base/$bare_id"];
        }

        $cover_images = array_map([$this, 'getMediumCoverImage'], $cover_images);
        $cover_images = array_filter($cover_images, [$this, 'removeAmazonCoverImages']);
        $cover_images = array_values($cover_images);

        if ($cover_images[0] === 'no_cover') {
            $cover_images = [false];
        }

        return $cover_images;
    }

    /**
     * Replace small Syndetics thumbs with medium-sized
     *
     * @param $image_url
     * @return string
     */
    private function getMediumCoverImage($image_url): string
    {
        return str_replace(
            ['/SC.JPG', 'https://proxy-na.hosted.exlibrisgroup.com/exl_rewrite/lib.syndetics'],
            ['/MC.JPG', 'http://lib.syndetics'],
            $image_url
        );
    }

    /**
     * Don't use Amazon images
     *
     * @param $image_url
     * @return bool
     */
    private function removeAmazonCoverImages($image_url): bool
    {
        return (!strpos($image_url, 'amazon.com'));
    }
}
