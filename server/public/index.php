<?php

use BCLib\BCBento\Cache;
use BCLib\BCBento\JSONPWrapper;

require_once('../vendor/autoload.php');

$config = require('../config/.env.production.php');

$app = new \Slim\Slim($config);

require_once('../factories.php');
require_once('../errors.php');

$seconds_until_3am = secondsUntil3AM();

$paths = [

    '/typeahead'  => $seconds_until_3am,
    '/catalog'    => 180,
    '/articles'   => $seconds_until_3am,
    '/librarians' => $seconds_until_3am,
    '/guides'     => $seconds_until_3am,
    '/dpla'       => $seconds_until_3am,
    '/worldcat'   => $seconds_until_3am
];


foreach ($paths as $path => $ttl) {
    $service_name = ltrim($path, '/');
    $app->get(
        $path,
        function () use ($app, $service_name) {
            $service = $app->$service_name;
            $app->response->setBody(json_encode($service->fetch($app->request->params('any'))));
        }
    )->ttl = $ttl;
}

$app->response->headers->set('Content-Type', 'application/json');
$app->add(new Cache($app->redis));
$app->add(new JSONPWrapper());
$app->run();

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
    $three_am = strtotime("03:00");
    if ($now < $three_am) {
        $remaining = $three_am - $now;
    } else {
        $remaining = $three_am + 86400 - $now;
    }
    return $remaining;
}