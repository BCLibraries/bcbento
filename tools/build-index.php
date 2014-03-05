<?php

require_once __DIR__ . '/../server/vendor/autoload.php';

$simplexml = simplexml_load_file("/Users/florinb/tmp/libguides_export.xml");

$count = 0;

$bulk = [];

foreach ($simplexml->GUIDES->GUIDE as $guide) {
    if ($guide->STATUS == 'Unpublished' || $guide->STATUS == 'Private') {
        continue;
    }

    $guide_url = $guide->FRIENDLY_URL ? $guide->FRIENDLY_URL : $guide->URL;

    $taxonomy = [];

    foreach ($guide->PAGES->PAGE as $page) {
        $page_name = $page->NAME;
        $page_text = [];

        $bulk_instruction = [
            'index' => [
                '_index' => 'libguides_v0',
                '_type'  => 'guide',
                '_id'    => (STRING) $page->PAGE_ID
            ]
        ];
        $bulk[] = json_encode($bulk_instruction, JSON_UNESCAPED_SLASHES);

        foreach ($page->BOXES->BOX as $box) {
            $box_contents = htmlspecialchars_decode((STRING) $box->DESCRIPTION);
            $box_contents = strip_tags($box_contents);
            $page_text[] = (STRING) $box->NAME . ' ******* ' . $box_contents;
        }

        $bulk[] = json_encode(
            [
                'guide_id'          => (STRING) $guide->GUIDE_ID,
                'guide_description' => (STRING) $guide->DESCRIPTION,
                'tags'              => (STRING) $guide->TAGS,
                'guide_name'        => (STRING) $guide->NAME,
                'guide_url'         => (STRING) $guide_url,
                'page_name'         => (STRING) $page->NAME,
                'page_id'           => (STRING) $page->PAGE_ID,
                'taxonomy'          => [],
                'page_text'         => $page_text,
                'page_url'          => htmlspecialchars_decode($page->URL)
            ],
            JSON_UNESCAPED_SLASHES
        );

        $count++;

    }


}


$bulk_string = implode("\n", $bulk) . "\n";
$bulk_string = str_replace('\u00a0', ' ', $bulk_string);

$es = new Elasticsearch\Client(['hosts' => ['http://twf4on0305onei.bc.edu:9200/']]);
$es->bulk(['body' => $bulk_string]);

echo "Wrote $count\n";