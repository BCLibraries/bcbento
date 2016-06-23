<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BriefSearchResult;

abstract class Service
{
    abstract public function fetch($keyword);

    abstract protected function buildResponse(BriefSearchResult $result, $keyword);

}