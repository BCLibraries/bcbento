<?php

declare(strict_types=1);

use BCLib\BCBento\Website\Indexer;
use Elasticsearch\Client;

require_once __DIR__ . '/../vendor/autoload.php';

// Load configuration
$config = require __DIR__ . '/../config/.env.production.php';
$es_host = $config['ELASTICSEARCH_HOST'];

// Build dependencies.
$index_name = "website_" . time();
$es = new Client(['hosts' => [$es_host]]);
$indexer = new Indexer(
    $es,
    $config['LIBGUIDES_SITE_ID'],
    $config['LIBGUIDES_API_KEY'],
    $index_name
);

build_index($es, $index_name);
$indexer->indexSite();
swap_aliases($es, $index_name);

function build_index(Client $es, string $index_name)
{
    $schema_json = file_get_contents(__DIR__ . '/website-schema.json');
    $schema = json_decode($schema_json, true);
    $idx_params = [
        'index' => $index_name,
        'body'  => $schema
    ];
    $es->indices()->create($idx_params);
}

function swap_aliases(Client $es, string $new_idx)
{
    // Move old index to the rollback index and delete the old rollback.
    $old_idx = get_index_name($es, 'website');
    $es->indices()->delete(['index' => 'website_rollback']);
    $es->indices()->deleteAlias(['name' => 'website', 'index' => $old_idx]);
    $es->indices()->putAlias(['index' => $old_idx, 'name' => 'website_rollback']);

    // Update the website aliast to the new index.
    $es->indices()->putAlias(['index' => $new_idx, 'name' => 'website']);
}

function get_index_name(Client $es, string $alias) : string
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