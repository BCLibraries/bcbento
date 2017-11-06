<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BibRecord;
use BCLib\PrimoServices\BriefSearchResult;
use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\Query;
use BCLib\PrimoServices\QueryBuilder;

abstract class AbstractPrimoService implements ServiceInterface
{
    const TYPE_MAP = [
        'book'                => 'Book',
        'video'               => 'Video',
        'journal'             => 'Journal',
        'government_document' => 'Government document',
        'database'            => 'Database',
        'image'               => 'Image',
        'audio_music'         => 'Musical recording',
        'realia'              => '',
        'data'                => 'Data',
        'dissertation'        => 'Thesis',
        'article'             => 'Article',
        'review'              => 'Review',
        'reference_entry'     => 'Reference entry',
        'newspaper_article'   => 'Newspaper article',
        'other'               => ''
    ];

    /**
     * @var PrimoServices
     */
    protected $primo;

    /**
     * @var QueryBuilder
     */
    protected $query_builder;

    public function __construct(
        PrimoServices $primo,
        QueryBuilder $query_builder
    ) {
        $this->primo = $primo;
        $this->query_builder = $query_builder;
    }

    public function fetch($keyword)
    {
        $result = $this->primo->search($this->getQuery($keyword));
        return $this->buildResponse($result, $keyword);
    }

    protected function searchPermalink($keyword, bool $is_pci = false)
    {
        $base = "https://{$this->primo->getHost()}/primo-explore/search";
        $query_params = [
            'query'        => "any,contains,$keyword",
            'tab'          => $is_pci ? 'pci_only' : 'bcl_only',
            'search_scope' => $is_pci ? 'pci' : 'bcl',
            'vid'          => 'bclib_new',
            'lang'         => 'en_US',
            'offset'       => 0
        ];
        return $base . '?' . http_build_query($query_params);
    }

    protected function itemPermalink(BibRecord $result, bool $is_pci = false): string
    {
        $base = "https://{$this->primo->getHost()}/primo-explore/fulldisplay";
        $query_params = [
            'docid'        => $result->id,
            'context'      => $is_pci ? 'PC' : 'L',
            'tab'          => $is_pci ? 'pci_only' : 'bcl_only',
            'search_scope' => $is_pci ? 'pci' : 'bcl',
            'vid'          => 'bclib_new',
            'lang'         => 'en_US',
        ];
        return $base . '?' . http_build_query($query_params);
    }

    abstract protected function getQuery($keyword): Query;

    abstract protected function buildResponse(BriefSearchResult $result, $keyword);

    protected function displayType(BibRecord $item)
    {
        return self::TYPE_MAP[$item->type] ?? $item->type;
    }
} 
