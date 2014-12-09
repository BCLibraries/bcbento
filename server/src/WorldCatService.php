<?php

namespace BCLib\BCBento;

use Doctrine\Common\Cache\Cache;
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

    const CACHE_ID = 'worldcat-discovery-token';

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

    public function __construct($key, $secret, $institution_id, $institution_code, Cache $cache)
    {
        $this->cache = $cache;
        $this->key = $key;
        $this->secret = $secret;
        $this->institution_code = $institution_code;
        $this->institution_id = $institution_id;
    }

    public function fetch($keyword)
    {
        $still_searching = true;
        $num_tries = 0;

        $bib = '';

        while ($still_searching && $num_tries < self::MAX_ACCESS_TOKEN_RETRIES) {

            if (!$this->cache->contains(self::CACHE_ID)) {
                $this->access_code = $this->fetchAccessCode();
                $ttl = $this->access_code->getExpiresIn() - 10;
                $this->cache->save(self::CACHE_ID, $this->access_code, $ttl);
            } else {
                $this->access_code = $this->cache->fetch(self::CACHE_ID);
            }

            $bib = $this->search($keyword);

            if (is_a($bib, '\WorldCat\Discovery\Error')) {
                $still_searching = $this->handleError($bib);
            } else {
                $still_searching = false;
            }

            $num_tries++;
        }

        return $this->formatResult($bib);
    }

    private function fetchAccessCode()
    {
        $options = array('services' => array('WorldCatDiscoveryAPI', 'refresh_token'));
        $ws_key = new WSKey($this->key, $this->secret, $options);
        return $ws_key->getAccessTokenWithClientCredentials($this->institution_id, $this->institution_id);
    }

    private function handleError(Error $error)
    {
        switch ($error->getErrorCode()) {
            case '403':
                echo "403";
                echo $error->getErrorMessage() . "<br>\n";
                return true;
            default:
                echo "HERER!!!!\n";
                throw new \Exception($error->getErrorMessage(), $error->getErrorCode());
        }
        return false;
    }

    private function search($keyword)
    {
        $options = ['dbIds' => '283', 'notHeldBy' => $this->institution_code];
        return Bib::search($keyword, $this->access_code, $options);
    }

    private function formatResult(BibSearchResults $results)
    {
        $response = [];
        foreach ($results->getSearchResults() as $book) {
            $response[] = $this->formatCreativeWork($book);
            if (count($response) >= self::MAX_RESPONSE_ITEMS) {
                break;
            }
        }
        return $response;
    }

    private function formatCreativeWork(CreativeWork $work)
    {
        $response = new \stdClass();
        $response->name = (string) $work->getName();
        $response->type = (string) $work->getType();
        $response->url = (string) $work->getUri();

        $response->url = str_replace('www.worldcat.org', 'bc.worldcat.org', $response->url);


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
}