<?php

use BCLib\BCBento\Cache;
use BCLib\BCBento\JSONPWrapper;

require_once('../vendor/autoload.php');

$config = require('../config/.env.production.php');

$app = new \Slim\Slim($config);

require_once('../factories.php');
require_once('../errors.php');

$paths = [
    '/typeahead',
    '/catalog',
    '/articles',
    '/librarians',
    '/guides',
    '/dpla',
    '/worldcat'
];

foreach ($paths as $path) {
    $service_name = ltrim($path, '/');
    $app->get(
        $path,
        function () use ($app, $service_name) {
            $service = $app->$service_name;
            $app->response->setBody(json_encode($service->fetch($app->request->params('any'))));
        }
    );
}

$app->response->headers->set('Content-Type', 'application/json');
$app->add(new Cache($app->redis));
$app->add(new JSONPWrapper());
$app->run();