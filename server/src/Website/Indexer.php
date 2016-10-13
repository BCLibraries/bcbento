<?php

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

    const CRAWL_DELAY = 10;

    public function __construct(Client $elastic, $site_id, $api_key, $index_name)
    {
        $this->elastic = $elastic;
        $this->site_id = $site_id;
        $this->api_key = $api_key;
        $this->index_name = $index_name;
    }

    public function indexSite()
    {
        $guides = $this->fetchGuides();
        foreach ($guides as $guide) {
            array_walk($guide->pages, [$this, 'indexPage'], $guide);
        }
    }

    public function indexPage(Page $page, $key, Guide $guide)
    {
        echo "Indexed {$guide->title} : {$page->title}\n";
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
                'guide_description' => $guide->description
            ]
        ];
        $response = $this->elastic->index($params);

        // Wait out the crawl delay
        sleep(Indexer::CRAWL_DELAY);

        return $response;
    }

    /**
     * @return Guide[]
     */
    private function fetchGuides()
    {
        $query_string = [
            'site_id' => $this->site_id,
            'key'     => $this->api_key,
            'expand'  => 'pages,tags,subjects',
            'status'  => '1'
        ];
        $url = 'http://lgapi.libapps.com/1.1/guides?' . http_build_query($query_string);
        $guides_json = $this->getJSON($url);
        return array_map([$this, 'buildGuide'], $guides_json);
    }

    private function buildGuide($guide_json)
    {
        $guide = new Guide();
        $guide->id = $guide_json->id;
        $guide->title = $guide_json->name;
        $guide->url = isset($guide_json->friendly_url) ? $guide_json->friendly_url : $guide_json->url;
        $guide->description = (isset($guide_json->description)) ? $guide_json->description : '';
        $guide->subjects = isset($guide_json->subjects) ? $this->buildSubjects($guide_json->subjects) : [];
        $guide->tags = isset($guide_json->tags) ? $this->buildTags($guide_json->tags) : [];
        array_walk($guide_json->pages, [$this, 'buildPage'], $guide);
        return $guide;
    }

    function buildPage($page_json, $key, Guide $guide)
    {
        if ($page_json->enable_display) {
            $page = new Page();
            $page->id = $page_json->id;
            $page->title = $page_json->name;
            $page->updated = $page_json->updated;
            $page->guide = $guide;
            $page->url = isset($page_json->friendly_url) ? $page_json->friendly_url : $page_json->url;
            $guide->pages[] = $page;
        }
    }

    function buildSubjects($subjects_json)
    {
        $subjects = array_map(
            function ($subject) {
                return $subject->name;
            },
            $subjects_json
        );
        return $subjects;
    }

    function buildTags($tags_json)
    {
        $tags = array_map(
            function ($tag) {
                return $tag->text;
            },
            $tags_json
        );
        return $tags;
    }

    private function getJSON($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
}