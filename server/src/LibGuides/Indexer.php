<?php

namespace BCLib\BCBento\LibGuides;

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
            array_walk($guide->pages, [$this, 'indexPage']);
        }
    }

    public function indexPage(Page $page)
    {
        $page->crawl();
        $params = [
            'index' => $this->index_name,
            'type'  => 'page',
            'id'    => $page->id,
            'body'  => [
                'title'       => $page->title,
                'guide_title' => $page->guide->title,
                'guide_id'    => $page->guide->id,
                'text'        => $page->text,
                'url'         => $page->url,
                'guide_url'   => $page->guide->url,
                'updated'     => $page->updated
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
        $url = "http://lgapi.libapps.com/1.1/guides?site_id={$this->site_id}&key={$this->api_key}&expand=pages&status=1";
        $guides_json = $this->getJSON($url);
        return array_map([$this, 'buildGuide'], $guides_json);
    }

    private function buildGuide($guide_json)
    {
        $guide = new Guide();
        $guide->id = $guide_json->id;
        $guide->title = $guide_json->name;
        $guide->url = isset($guide_json->friendly_url) ? $guide_json->friendly_url : $guide_json->url;
        array_walk($guide_json->pages, [$guide, 'addPage']);
        return $guide;
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