<?php

namespace BCLib\BCBento;

use BCLib\PrimoClient\PrimoClient;
use BCLib\PrimoClient\QueryConfig;

class NewCatalogService implements ServiceInterface
{
    /**
     * @var PrimoClient
     */
    private $client;

    public function __construct(PrimoClient $client)
    {
        $this->client = $client;
    }

    public function fetch($keyword): SearchResponse
    {
        $api_response = $this->client->search($keyword);

        foreach ($api_response->docs as $doc) {
            $doc->json = '';
        }

        $response = new SearchResponse($api_response->docs, '', $api_response->total);
        $response->addField('dym', $api_response->did_u_mean);
        $response->addField('controlled_vocabulary', $api_response->controlled_vocabulary);
        return $response;
    }
}