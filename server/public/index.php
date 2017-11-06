<?php

use BCLib\BCBento\Cache;
use BCLib\BCBento\JSONPWrapper;
use Slim\Slim;

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/.env.production.php';

$app = new Slim($config);

require_once __DIR__ . '/../provision.php';
require_once __DIR__ . '/../errors.php';

$seconds_until_3am = secondsUntil3AM();

$paths = [
    '/typeahead'   => $seconds_until_3am,
    '/catalog'     => 180,
    '/articles'    => $seconds_until_3am,
    '/librarians'  => $seconds_until_3am,
    '/guides'      => $seconds_until_3am,
    '/dpla'        => $seconds_until_3am,
    '/worldcat'    => $seconds_until_3am,
    '/springshare' => $seconds_until_3am,
    '/website'     => $seconds_until_3am,
    '/faq'         => $seconds_until_3am
];


foreach ($paths as $path => $ttl) {
    $service_name = ltrim($path, '/');
    $app->get(
        '/:version' . $path,
        function () use ($app, $service_name) {
            $service = $app->$service_name;
            $app->response->setBody(json_encode($service->fetch($app->request->params('any'))));
        }
    )->ttl = $ttl;

    /**
     * @todo deprecate and remove fix for un-versioned API calls.
     */
    $app->get(
        "/$service_name",
        function () use ($app, $service_name) {
            $service = $app->$service_name;
            $fetch_response = $service->fetch($app->request->params('any'));
            if (is_array($fetch_response) && isset($fetch_response['error_code'])) {
                $app->response->setStatus($fetch_response['error_code']);
            }
            $app->response->setBody(json_encode($fetch_response));
        }
    )->ttl = 100;
}

$app->get(
    '/primo-catalog',
    function () use ($app) {
        redirectToPrimo($app);
    }
);

$app->get(
    '/primo-articles',
    function () use ($app) {
        redirectToPrimo($app, true);
    }
);

$app->add(new Cache($app->redis));
$app->add(new JSONPWrapper());
$app->run();

function redirectToPrimo(Slim $app, $is_pci = false)
{
    $base = "https://{$app->config('PRIMO_HOST')}/primo-explore/search";
    $query_params = [
        'query'        => "any,contains,{$app->request->params('any')}",
        'tab'          => $is_pci ? 'pci_only' : 'bcl_only',
        'search_scope' => $is_pci ? 'pci' : 'bcl',
        'vid'          => 'bclib_new',
        'lang'         => 'en_US',
        'offset'       => 0
    ];
    $url = $base . '?' . http_build_query($query_params);
    $app->redirect($url);
}

/**
 * Get seconds until 3am
 *
 * Most things are cached until 3am the next morning.
 *
 * @return int
 */
function secondsUntil3AM()
{
    $now = time();
    $three_am = strtotime('03:00');
    if ($now < $three_am) {
        $remaining = $three_am - $now;
    } else {
        $remaining = $three_am + 86400 - $now;
    }
    return $remaining;
}