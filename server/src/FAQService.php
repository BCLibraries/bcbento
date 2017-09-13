<?php

namespace BCLib\BCBento;

use Guzzle\Http\Client;

class FAQService implements ServiceInterface
{
    public function fetch($keyword)
    {
        $client = new Client();
        $request = $client->get($this->url($keyword));
        $body = $request->send()->getBody();
        $remote_response = json_decode($body);
        return $this->buildResponse($remote_response, $keyword);
    }

    private function url($keyword)
    {
        return "https://api2.libanswers.com/1.0/search/$keyword?iid=45";
    }

    private function buildResponse($service_response, $keyword)
    {
        $search_response = $service_response->search;

        return [
            'total_results' => $search_response->numFound,
            'search_url'   => "http://answers.bc.edu/search/?t=0&q=$keyword",
            'dym'           => null,
            'items'         => array_map([$this, 'processResult'], $search_response->results)
        ];
    }

    private function processResult($result)
    {
        return [
            'question' => $result->question,
            'url'      => $result->url
        ];
    }

}