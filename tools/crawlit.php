<?php

require_once __DIR__ . '/../server/vendor/autoload.php';

$es = new Elasticsearch\Client(['hosts' => ['http://twf4on0305onei.bc.edu:9200/']]);

$ninas_guides = [
    'http://libguides.bc.edu/americanpolitics',
    'http://libguides.bc.edu/asianstudies',
    'http://libguides.bc.edu/asianstudiesportal',
    'http://libguides.bc.edu/TextandTech',
    'http://libguides.bc.edu/InternationalStudies',
    'http://libguides.bc.edu/islamicportal',
    'http://libguides.bc.edu/islamic',
    'http://libguides.bc.edu/jewishstudiesportal',
    'http://libguides.bc.edu/linguistics',
    'http://libguides.bc.edu/linguisticsportal',
    'http://libguides.bc.edu/IntroductionModernPolitics',
    'http://libguides.bc.edu/WomenAndPolitics',
    'http://libguides.bc.edu/RightsInConflict',
    'http://libguides.bc.edu/AnatomyofDictatorship',
    'http://libguides.bc.edu/LatinAmericanPolitics',
    'http://libguides.bc.edu/ChildrensRights',
    'http://libguides.bc.edu/HumanRights',
    'http://libguides.bc.edu/LiberalismAndForeignPolicy',
    'http://libguides.bc.edu/InternationalInstitutions',
    'http://libguides.bc.edu/PoliticsofEnergy',
    'http://libguides.bc.edu/InstitutionsInternationalPolitics',
    'http://libguides.bc.edu/JapanesePolitics',
    'http://libguides.bc.edu/PoliticsJapanKorea',
    'http://libguides.bc.edu/polisciportal',
    'http://libguides.bc.edu/russian',
    'http://libguides.bc.edu/slavicportal'
];

foreach ($ninas_guides as $link) {
    $pid = '';

    $dom = new DOMDocument();
    @$dom->loadHTMLFile($link);
    $xpath = new DOMXPath($dom);

    $xpath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');
    $tax_attrs = $xpath->query('//meta[@name="BC tags"]/@content');

    $taxonomy = [];

    for ($i = 0; $i < $tax_attrs->length; $i++) {
        $taxonomy = array_merge($taxonomy, explode('; ', trim($tax_attrs->item($i)->value)));
    }

    $id_attr = $xpath->query('//meta[@name="DC.Identifier"]/@content');
    $url_parts = parse_url($id_attr->item(0)->value);
    parse_str($url_parts['query']);

    $search_body = [
        "query" => [
            "match" => [
                "guide_id" => [
                    "query" => $pid
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
        $update_query = update_query($hit['_id'], $taxonomy);
        $es->update($update_query);
    }

    sleep(5);
}


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
