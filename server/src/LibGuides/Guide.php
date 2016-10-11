<?php

namespace BCLib\BCBento\LibGuides;

class Guide
{
    public $id;
    public $title;
    public $url;

    /**
     * @var Page[]
     */
    public $pages = [];

    function addPage($page_json)
    {
        if ($page_json->enable_display) {
            $page = new Page();
            $page->id = $page_json->id;
            $page->title = $page_json->name;
            $page->updated = $page_json->updated;
            $page->guide = $this;
            $page->url = isset($page_json->friendly_url) ? $page_json->friendly_url : $page_json->url;
            $this->pages[] = $page;
        }
    }
}