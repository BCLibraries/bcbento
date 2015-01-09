<?php

namespace BCLib\BCBento;

use Slim\Middleware;

class Cache extends Middleware
{
    /**
     * @var \Doctrine\Common\Cache\Cache
     */
    private $cache;

    public function __construct(\Doctrine\Common\Cache\Cache $cache)
    {
        $this->cache = $cache;
    }


    public function call()
    {
        $key = $this->getKey();

        $response = $this->app->response();

        if ($this->cache->contains($key)) {
            $response->body($this->cache->fetch($key));
            return;
        }

        $this->next->call();

        if ($response->status() === 200) {
            $this->cache->save($key, $response->body());
        }
    }

    private function getKey()
    {
        $query_keys = $_GET;
        unset($query_keys['callback']);
        asort($query_keys);
        $key = $this->app->request()->getResourceUri() . "?" . http_build_query($query_keys);
        return $key;
    }

}