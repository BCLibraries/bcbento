<?php

namespace BCLib\BCBento;

use TFN\DPLA;

class DPLAService implements ServiceInterface
{
    /**
     * @var \TFN\DPLA
     */
    private $dpla;

    public function __construct(DPLA $dpla)
    {
        $this->dpla = $dpla;
    }

    public function fetch($keyword): \stdClass
    {
        $result = $this->dpla->createSearchQuery()->forText($keyword)
            ->withPaging(1, 4)->execute();
        $response = new \stdClass();
        $response->total = $result->getTotalCount();
        $response->link = 'http://dp.la/search?utf8=%E2%9C%93&q=' . $keyword;
        return $response;
    }
}