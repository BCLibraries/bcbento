<?php

require_once __DIR__ . '/../vendor/autoload.php';

$index_name = "website_" . time();

$config = require __DIR__ .'/../config/.env.production.php';
$libguides_site_id = $config['LIBGUIDES_SITE_ID'];
$libguides_api_key = $config['LIBGUIDES_API_KEY'];
$es_host = $config['ELASTICSEARCH_HOST'];

$es = new \Elasticsearch\Client(['hosts' => [$es_host]]);
$indexer = new \BCLib\BCBento\Website\Indexer($es, $libguides_site_id, $libguides_api_key, $index_name);

build_index($es, $index_name);
$indexer->indexSite();
swap_aliases($es, $index_name);

function build_index(\Elasticsearch\Client $es, $index_name)
{
    $schema = json_decode(file_get_contents(__DIR__ . '/website-schema.json'), true);
    $params = [
        'index' => $index_name,
        'body'  => $schema
    ];
    $es->indices()->create($params);
}

function swap_aliases(\Elasticsearch\Client $es, $new_idx)
{
    $old_website_idx = get_alias($es, 'website');

    $es->indices()->delete(['index' => 'website_rollback']);

    $es->indices()->deleteAlias(['name' => 'website', 'index' => $old_website_idx]);
    $es->indices()->putAlias(['index' => $new_idx, 'name' => 'website']);
    $es->indices()->putAlias(['index' => $old_website_idx, 'name' => 'website_rollback']);
}

function get_alias(\Elasticsearch\Client $es, $alias)
{
    $index = '';
    try {
        $response = $es->indices()->getAlias(['name' => $alias]);
        $index = array_keys($response)[0];
    } catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
        // Ignore 404s
    }
    return $index;
}