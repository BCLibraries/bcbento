<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BibRecord;
use Doctrine\Common\Cache\RedisCache;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;

class VideoThumbClient
{
    private const SRC_ALEXANDER = 'ALEXANDER-STREET';
    private const SRC_MEDICITV = 'MEDICITV';

    private const SOURCE_REGEXES = [
        self::SRC_ALEXANDER => '#(https?://www.aspresolver.*)\$\$D#',
        self::SRC_MEDICITV  => '#(https?://edu.medici.tv/movies.*)\$\$D#'
    ];

    const REQUEST_OPTIONS = ['allow_redirects' => true];

    /**
     * @var Client
     */
    private $http;

    /**
     * @var BibRecord[]
     */
    private $queue = [];

    /**
     * @var RedisCache
     */
    private $cache;

    public function __construct(Client $http, RedisCache $cache)
    {
        $this->http = $http;
        $this->cache = $cache;
    }

    public function queue(BibRecord $item): void
    {
        $this->queue[$item->id] = $item;
    }

    public function fetch(): array
    {
        $results = [];
        $promises = [];

        foreach ($this->queue as $item) {
            if ($this->cache->contains($this->cacheKey($item->id))) {
                $results[$item->id] = $this->readCache($item->id);
            } elseif ($this->isFilmsOnDemand($item)) {
                $results[$item->id] = $this->getFilmsOnDemandScreenCap($item);
            } else {
                $promises[$item->id] = $this->sendThumbRequest($item);
            }
        }
        $responses = Promise\settle($promises)->wait();

        foreach ($responses as $id => $promise) {
            $results[$id] = $promise['value'];
            $this->save($id, $results[$id]);
        }
        return $results;
    }

    private function cacheKey($id): string
    {
        return "bcbento:video-thumb:$id";
    }

    private function save(string $id, ?string $thumb): void
    {
        $this->cache->save($this->cacheKey($id), $thumb);
    }

    private function readCache(string $id)
    {
        return $this->cache->fetch($this->cacheKey($id));
    }

    /**
     * @param BibRecord $item
     * @return PromiseInterface
     */
    private function sendThumbRequest(BibRecord $item): ?PromiseInterface
    {
        $link = $item->field('links/linktorsrc');

        if (is_array($link)) {
            $link = $link[0];
        }

        if (!$link) {
            return null;
        }

        foreach (self::SOURCE_REGEXES as $source => $regex) {
            if ($promise = $this->send($regex, $link, $source)) {
                return $promise;
            }
        }

        return null;
    }

    private function getScreenCap(string $html, string $source): ?string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);

        switch ($source) {
            case self::SRC_ALEXANDER:
                return $this->getAlexanderScreenCap($dom);
            case self::SRC_MEDICITV:
                return $this->getOpenGraphImage($dom);
        }

        return null;
    }

    private function getAlexanderScreenCap(\DOMDocument $dom): ?string
    {
        $xpath = new \DOMXPath($dom);
        $imgs = $xpath->query("//script[@type='application/ld+json']");
        if ($imgs->length > 0) {
            $json = $imgs->item(0)->textContent;
            $decoded = json_decode($json);
            return (string) $decoded->thumbnail->contentUrl;
        }
        return null;
    }

    private function getOpenGraphImage(\DOMDocument $dom): ?string
    {
        $xpath = new \DOMXPath($dom);
        $metas = $xpath->query("//meta[@property='og:image']");
        if ($metas->length > 0) {
            return (string) $metas->item(0)->getAttribute('content');
        }
        return null;
    }

    private function send(string $regex, string $link, string $source): ?PromiseInterface
    {
        preg_match($regex, $link, $matches);
        if (isset($matches[1])) {
            return $this->http->getAsync($matches[1], self::REQUEST_OPTIONS)
                ->then(
                    function ($response) use ($source) {
                        $html = (string) $response->getBody();
                        return $this->getScreenCap($html, $source);
                    }
                );
        }
        return null;
    }

    private function isFilmsOnDemand(BibRecord $item): bool
    {
        $source = $item->field('display/lds30');
        return $source === 'FILMS ON DEMAND';
    }

    private function getFilmsOnDemandScreenCap(BibRecord $item): string
    {
        $pattern = '/xtid=(\d*)\$\$/';
        $link = $item->field('links/linktorsrc');

        preg_match($pattern, $link, $matches);

        if (isset($matches[1])) {
            $url = "https://fod.infobase.com/image/{$matches[1]}";
        }
        return $url;
    }

}