<?php

namespace BCLib\BCBento;

class FAQService implements ServiceInterface
{
    public function fetch($keyword)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL            => $this->url($keyword),
                CURLOPT_USERAGENT      => 'Codular Sample cURL Request',
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => 30
            )
        );
        $resp = curl_exec($curl);
        curl_close($curl);

        $remote_response = $resp ? $this->buildResponse(json_decode($resp), $keyword) : ['error_code' => 500];

        return $remote_response;
    }

    private function url($keyword)
    {
        return "https://api2.libanswers.com/1.0/search/$keyword?iid=45&limit=5";
    }

    private function buildResponse($service_response, $keyword)
    {
        $search_response = $service_response->search;

        return [
            'total_results' => $search_response->numFound,
            'search_url'    => "http://answers.bc.edu/search/?t=0&q=$keyword",
            'dym'           => null,
            'items'         => array_map([$this, 'processResult'], $search_response->results)
        ];
    }

    private function processResult($result)
    {
        return [
            'id'       => $result->id,
            'question' => $result->question,
            'url'      => $result->url
        ];
    }

}