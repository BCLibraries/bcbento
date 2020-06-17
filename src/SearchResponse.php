<?php

namespace BCLib\BCBento;

class SearchResponse implements \JsonSerializable
{
    private $result = [];

    public function __construct(array $items, $search_url, $total_results)
    {
        if (!\is_string($search_url)) {
            throw new \InvalidArgumentException('Search url strings must be strings, got ' . \gettype($search_url));
        }
        $this->result['items'] = $items;
        $this->result['search_url'] = $search_url;
        $this->result['total_results'] = (int) $total_results;
    }

    public function addField($key, $value)
    {
        $this->result[$key] = $value;
    }

    public function jsonSerialize()
    {
        return $this->result;
    }
}