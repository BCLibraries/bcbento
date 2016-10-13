<?php

namespace BCLib\BCBento\Website;

class Guide
{
    public $id;
    public $title;
    public $url;
    public $tags =[];
    public $subjects = [];
    public $description;

    /**
     * @var Page[]
     */
    public $pages = [];
}