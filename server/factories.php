<?php

use BCLib\BCBento\ArticlesService;
use BCLib\BCBento\CatalogService;
use BCLib\BCBento\GuidesService;
use BCLib\BCBento\LibrariansService;
use BCLib\BCBento\TypeaheadService;
use BCLib\PrimoServices\DeepLink;
use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\QueryBuilder;
use Doctrine\Common\Cache\RedisCache;
use Elasticsearch\Client;

$app->primo = function () use ($app) {
    return new PrimoServices(
        $app->config('PRIMO_HOST'),
        $app->config('PRIMO_INSTITUTION'),
        $app->redis
    );
};

$app->deeplink = function () use ($app) {
    return new DeepLink($app->config('PRIMO_HOST'), $app->config('PRIMO_INSTITUTION'));
};

$app->qb = function () use ($app) {
    return new QueryBuilder($app->config('PRIMO_INSTITUTION'));
};

$app->redis = function () use ($app) {
    $redis = new Redis();
    $redis->connect($app->config('REDIS_HOST'));
    $cache = new RedisCache();
    $cache->setRedis($redis);
    return $cache;
};

$app->elasticsearch = function () use ($app) {
    return new Client(['hosts' => [$app->config('ELASTICSEARCH_HOST')]]);
};

$app->typeahead = function () use ($app) {
    return new TypeaheadService($app->elasticsearch);
};

$app->articles = function () use ($app) {
    return new ArticlesService($app->primo, $app->qb);
};

$app->catalog = function () use ($app) {
    return new CatalogService($app->primo, $app->qb);
};

$app->guides = function () use ($app) {
    return new GuidesService($app->elasticsearch);
};

$app->librarians = function () use ($app) {
    return new LibrariansService($app->elasticsearch);
};

$app->dpla = function () use ($app) {
    require_once __DIR__ . '/vendor/3ft9/dpla/tfn/DPLA.php';
    return new \BCLib\BCBento\DPLAService(new \TFN\DPLA($app->config('DPLA_KEY')));
};

$app->worldcat = function () use ($app) {
    return new \BCLib\BCBento\WorldCatService(
        $app->config('WORLDCAT_KEY'),
        $app->config('WORLDCAT_SECRET'),
        $app->config('WORLDCAT_INST_NUM'),
        $app->config('WORLDCAT_INST_CODE'),
        $app->redis
    );
};