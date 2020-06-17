<?php

declare(strict_types=1);


namespace BCLib\BCBento\Website;

use Elasticsearch\Client;

class Indexer
{
    /**
     * @var \ElasticSearch\Client
     */
    private $elastic;
    private $site_id;
    private $api_key;
    private $index_name;

    /** @var boolean */
    private $verbose;

    // Taken from LibGuides' robots.txt
    const CRAWL_DELAY = 10;

    public function __construct(
        Client $elastic,
        int $site_id,
        string $api_key,
        string $index_name,
        bool $verbose = false
    ) {
        $this->elastic = $elastic;
        $this->site_id = $site_id;
        $this->api_key = $api_key;
        $this->index_name = $index_name;
        $this->verbose = $verbose;
    }

    public function indexSite()
    {
        $guides = $this->fetchGuides();

        $this->report('Indexing page');
        $page_index_function = [$this, 'indexPage'];
        foreach ($guides as $guide) {
            array_walk($guide->pages, $page_index_function, $guide);
        }

        $this->report('Finished.');
    }

    public function indexPage(Page $page, int $key, Guide $guide): array
    {
        $this->report("Crawling {$page->title}\n", 1);
        $page->crawl();
        $params = [
            'index' => $this->index_name,
            'type'  => 'page',
            'id'    => $page->id,
            'body'  => [
                'title'             => $page->title,
                'guide_title'       => $page->guide->title,
                'guide_id'          => $page->guide->id,
                'text'              => $page->text,
                'url'               => $page->url,
                'guide_url'         => $page->guide->url,
                'updated'           => $page->updated,
                'guide_subjects'    => $guide->subjects,
                'guide_tags'        => $guide->tags,
                'guide_description' => $guide->description,
                'canvas'            => $guide->canvas
            ]
        ];
        $this->report("Indexing {$page->title}\n", 1);
        $response = $this->elastic->index($params);

        // Wait out the crawl delay
        sleep(self::CRAWL_DELAY);

        return $response;
    }

    /**
     * @return Guide[]
     */
    private function fetchGuides(): array
    {
        $this->report('Fetching guides');

        $query_string = [
            'site_id' => $this->site_id,
            'key'     => $this->api_key,
            'expand'  => 'pages,tags,subjects,metadata',
            'status'  => '1'
        ];

        $url = 'http://lgapi-us.libapps.com/1.1/guides?'.http_build_query($query_string);
        $guides_json = $this->getJSON($url);

        $guide_build_function = [$this, 'buildGuide'];

        $this->report("Building guide array", 1);
        $guides = array_map($guide_build_function, $guides_json);

        $guide_count = count($guides);
        $this->report("Found $guide_count guides", 1);

        return $guides;
    }

    private function buildGuide(\stdClass $guide_json): Guide
    {
        $guide = new Guide();

        $canvas = [];
        $metadata = $guide_json->metadata ?? [];

        foreach ($metadata as $metadatum) {
            if ($metadatum->name === 'canvas') {
                $canvas[] = $metadatum->content;
            }
        }

        $guide->id = $guide_json->id;
        $guide->title = $guide_json->name;
        $guide->url = $guide_json->friendly_url ?? $guide_json->url;
        $guide->description = $guide_json->description ?? '';
        $guide->subjects = isset($guide_json->subjects) ? $this->buildSubjects($guide_json->subjects) : [];
        $guide->tags = isset($guide_json->tags) ? $this->buildTags($guide_json->tags) : [];
        $guide->canvas = $canvas;

        $page_build_function = [$this, 'buildPage'];
        array_walk($guide_json->pages, $page_build_function, $guide);

        return $guide;
    }

    public function buildPage(\stdClass $page_json, $key, Guide $guide)
    {
        if ($page_json->enable_display) {
            $page = new Page();
            $page->id = $page_json->id;
            $page->title = $page_json->name;
            $page->updated = $page_json->updated;
            $page->guide = $guide;
            $page->url = $page_json->friendly_url ?? $page_json->url;
            $guide->pages[] = $page;
        }
    }

    private function buildSubjects(array $subjects_json): array
    {
        $subjects = array_map(
            function ($subject) {
                return $subject->name;
            },
            $subjects_json
        );
        return $subjects;
    }

    private function buildTags(array $tags_json): array
    {
        $tags = array_map(
            function ($tag) {
                return $tag->text;
            },
            $tags_json
        );
        return $tags;
    }

    private function getCanvasMetadata($accumulator, \stdClass $metadata_object): array
    {
        if (null === $accumulator) {
            $accumulator = [];
        }
        $name = strtolower($metadata_object->name);
        if ($name === 'canvas') {
            $accumulator[] = $metadata_object->content;
        }
        return $accumulator;
    }

    private function getJSON(string $url)
    {
        $this->report("Requesting $url\n", 1);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            $this->report("HTTP error: $error_msg", 1);
            throw new \Exception("HTTP error fetching LibGuides from $url: $error_msg");
        }
        return json_decode($result);
    }

    private function report(string $message, int $tab_level = 0): void
    {
        if ($this->verbose) {
            $output = str_pad($message, $tab_level, "\t");
            echo "$output\n";
        }
    }
}