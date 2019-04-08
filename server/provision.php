<?php

use BCLib\BCBento\ArticlesService;
use BCLib\BCBento\CatalogService;
use BCLib\BCBento\DPLAService;
use BCLib\BCBento\FAQService;
use BCLib\BCBento\GuidesService;
use BCLib\BCBento\LibrariansService;
use BCLib\BCBento\SpringshareService;
use BCLib\BCBento\TypeaheadService;
use BCLib\BCBento\VideoService;
use BCLib\BCBento\VideoThumbClient;
use BCLib\PrimoServices\PrimoServices;
use BCLib\PrimoServices\QueryBuilder;
use Doctrine\Common\Cache\RedisCache;

$app->primo = function () use ($app) {
    return new PrimoServices(
        $app->config('PRIMO_HOST'), $app->config('PRIMO_INSTITUTION'), $app->redis, '4.9'
    );
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
    $hosts = [$app->config('ELASTICSEARCH_HOST')];
    return Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
};

$app->typeahead = function () use ($app) {
    return new TypeaheadService($app->elasticsearch);
};

$app->articles = function () use ($app) {
    return new ArticlesService($app->primo, $app->qb, 'pci_only', 'pci');
};

$app->catalog = function () use ($app) {
    return new CatalogService($app->primo, $app->qb, $app->worldcat);
};

$app->website = function () use ($app) {
    return new \BCLib\BCBento\WebsiteService($app->elasticsearch, $app->config('ELASTICSEARCH_VERSION'));
};

$app->guides = function () use ($app) {
    return new GuidesService(
        $app->elasticsearch, $app->config('ELASTICSEARCH_VERSION')
    );
};

$app->librarians = function () use ($app) {
    return new LibrariansService(
        $app->elasticsearch, $app->config('ELASTICSEARCH_VERSION')
    );
};

$app->dpla = function () use ($app) {
    require_once __DIR__.'/vendor/3ft9/dpla/tfn/DPLA.php';
    return new DPLAService(new \TFN\DPLA($app->config('DPLA_KEY')));
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

$app->springshare = function () {
    return new SpringshareService();
};

$app->faq = function () {
    return new FAQService();
};

$app->video = function () use ($app) {
    return new VideoService($app->primo, $app->qb, $app->video_thumbs);
};

$app->video_thumbs = function () use ($app) {
    return new VideoThumbClient(new GuzzleHttp\Client(), $app->redis);
};