<?php

use BCLib\BCBento\Cache;
use BCLib\BCBento\JSONPWrapper;
use BCLib\PrimoServices\DeepLink;
use BCLib\PrimoServices\QueryTerm;
use Slim\Slim;

require_once('../vendor/autoload.php');

$config = require('../config/.env.production.php');

$app = new Slim($config);

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
        '/:version' . $path,
        function () use ($app, $service_name, $path) {
            $service = $app->$service_name;
            $app->response->setBody(json_encode($service->fetch($app->request->params('any'))));
        }
    )->ttl = $ttl;
}

$app->get(
    '/primo-catalog',
    function () use ($app) {
        redirectToPrimo($app, $app->deeplink);
    }
);

$app->get(
    '/primo-articles',
    function () use ($app) {
        redirectToPrimo($app, $app->deeplink, true);
    }
);

$app->add(new Cache($app->redis));
$app->add(new JSONPWrapper());
$app->run();

function redirectToPrimo(Slim $app, DeepLink $dl, $articles = false)
{
    if ($articles) {
        $scope = 'pci';
        $tab = 'pci_only';
    } else {
        $scope = 'bcl';
        $tab = 'bcl_only';
    }

    $term = new QueryTerm();
    $term->keyword($app->request->params('any'));

    $dl->onCampus('TRUE');
    $dl->view('bclib');
    $dl->group('GUEST');
    $app->redirect('http://' . $dl->search($term) . "&search_scope=$scope&tab=$tab");
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
    $three_am = strtotime("03:00");
    if ($now < $three_am) {
        $remaining = $three_am - $now;
    } else {
        $remaining = $three_am + 86400 - $now;
    }
    return $remaining;
}