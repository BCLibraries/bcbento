<?php

namespace BCLib\BCBento;

use Doctrine\Common\Cache\Cache as DoctrineCache;
use OCLC\Auth\WSKey;
use WorldCat\Discovery\Bib;
use WorldCat\Discovery\BibSearchResults;
use WorldCat\Discovery\CreativeWork;
use WorldCat\Discovery\Error;
use WorldCat\Discovery\Thing;

class WorldCatService implements ServiceInterface
{
    const MAX_ACCESS_TOKEN_RETRIES = 3;

    const MAX_RESPONSE_ITEMS = 3;

    const TOKEN_CACHE_ID = 'worldcat-discovery-token';

    const TYPE_MAP = [
        'schema:Book'         => 'Book',
        'schema:VideoObject'  => 'Video',
        'schema:Periodical'   => 'Journal',
        'government_document' => 'Government document',
        'database'            => 'Database',
        'image'               => 'Image',
        'schema:MusicAlbum'   => 'Musical recording',
        'schema:CreativeWork' => '',
        'data'                => 'Data',
        'other'               => ''
    ];

    /**
     * @var \OCLC\Auth\AccessToken
     */
    private $access_code;

    /**
     * @var Cache
     */
    private $cache;

    private $key;
    private $secret;
    private $institution_id;
    private $institution_code;

    public function __construct($key, $secret, $institution_id, $institution_code, DoctrineCache $cache)
    {
        $this->cache = $cache;
        $this->key = $key;
        $this->secret = $secret;
        $this->institution_code = $institution_code;
        $this->institution_id = $institution_id;
    }

    public function fetch($keyword)
    {
        $cache_id = $this->requestCacheId($keyword);

        if ($this->cache->contains($cache_id)) {
            return $this->cache->fetch($cache_id);
        }

        $still_searching = true;
        $num_tries = 0;

        $bib = '';

        while ($still_searching && $num_tries < self::MAX_ACCESS_TOKEN_RETRIES) {

            if (!$this->cache->contains(self::TOKEN_CACHE_ID)) {
                $this->access_code = $this->fetchAccessCode();
                $ttl = $this->access_code->getExpiresIn() - 10;
                $this->cache->save(self::TOKEN_CACHE_ID, $this->access_code, $ttl);
            } else {
                $this->access_code = $this->cache->fetch(self::TOKEN_CACHE_ID);
            }

            $bib = $this->search($keyword);

            $still_searching = is_a($bib, Error::class) ? $this->handleError($bib) : false;

            $num_tries++;
        }

        $result = $this->formatResult($bib);

        $this->cache->save($cache_id, $result, 86400);

        return $result;
    }

    private function fetchAccessCode()
    {
        $options = array('services' => array('WorldCatDiscoveryAPI', 'refresh_token'));
        $ws_key = new WSKey($this->key, $this->secret, $options);
        return $ws_key->getAccessTokenWithClientCredentials($this->institution_id, $this->institution_id);
    }

    private function handleError(Error $error)
    {
        if ($error->getErrorCode() === '403') {
            return true;
        }
        throw new \Exception($error->getErrorMessage(), $error->getErrorCode());
    }

    private function search($keyword)
    {
        $options = ['dbIds' => '283', 'notHeldBy' => $this->institution_code];
        return Bib::search($keyword, $this->access_code, $options);
    }

    private function formatResult(BibSearchResults $results)
    {
        $wc_results = [];
        foreach ($results->getSearchResults() as $book) {
            $wc_results[] = $this->formatCreativeWork($book);
            if (count($wc_results) >= self::MAX_RESPONSE_ITEMS) {
                break;
            }
        }
        $response = ['total_results' => 0];
        if (count($wc_results) > 0) {
            $response['worldcat_results'] = $wc_results;
        }
        return $response;
    }

    private function formatCreativeWork(CreativeWork $work)
    {
        $response = new \stdClass();
        $response->name = (string) $work->getName();
        $response->type = (string) $this->displayType($work);
        $response->url = "https://bc.on.worldcat.org/oclc/{$work->getOCLCNumber()}";

        $response->creator = '';
        $creators = $work->getAuthors();

        foreach ($creators as $creator) {
            $response->creator .= $this->formatCreator($creator);
        }

        return $response;
    }

    private function formatCreator(Thing $creator)
    {
        return $creator->getName();
    }

    protected function displayType(CreativeWork $work)
    {
        $original_type = $work->type();
        return self::TYPE_MAP[$original_type] ?? $original_type;
    }

    private function requestCacheId($keyword)
    {
        $keyword = str_replace(' ', '+', $keyword);
        return 'worldcat-discovery-result-' . $keyword;
    }
}
