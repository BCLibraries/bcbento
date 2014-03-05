<?php

require_once __DIR__ . '/../server/vendor/autoload.php';

$guide_id = '884';

$term_string = 'Physics; Astronomy and Astrophysics';

$terms = explode('; ', $term_string);

$es = new Elasticsearch\Client(['hosts' => ['http://twf4on0305onei.bc.edu:9200/']]);

$search_body = [
    "query" => [
        "match" => [
            "guide_id" => [
                "query" => $guide_id
            ]
        ]
    ],
    "from"  => 0,
    "size"  => 200
];

$search_query = [
    'index' => 'libguides_v0',
    'body'  => $search_body
];

$search_result = $es->search($search_query);

foreach ($search_result['hits']['hits'] as $hit) {
    echo "Updating " . $hit['_id'] . "\n";
    $update_query = update_query($hit['_id'], $terms);
    $es->update($update_query);
}

echo "done\n";

function update_query($id, array $terms)
{

    return [
        "index" => "libguides_v0",
        "id"    => $id,
        "type"  => "guide",
        "body"  => [
            "script" => "ctx._source.taxonomy = taxonomy",
            "params" => [
                'taxonomy' => $terms
            ]
        ]
    ];
}