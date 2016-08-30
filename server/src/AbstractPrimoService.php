<?php

namespace BCLib\BCBento;

use BCLib\PrimoServices\BriefSearchResult;
use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\QueryBuilder;

abstract class AbstractPrimoService implements ServiceInterface
{

    /**
     * @var PrimoServices
     */
    protected $primo;

    /**
     * @var QueryBuilder
     */
    protected $query_builder;

    public function __construct(
        PrimoServices $primo,
        QueryBuilder $query_builder
    ) {
        $this->primo = $primo;
        $this->query_builder = $query_builder;
    }

    public function fetch($keyword)
    {
        $result = $this->primo->search($this->getQuery($keyword));
        return $this->buildResponse($result, $keyword);
    }

    /**
     * @param $keyword
     * @return  \BCLib\PrimoServices\Query
     */
    abstract protected function getQuery($keyword);

    abstract protected function buildResponse(BriefSearchResult $result, $keyword);

} 
