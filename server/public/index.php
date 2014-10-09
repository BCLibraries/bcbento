<?php

require_once('../vendor/autoload.php');

$config = require('../config/.env.production.php');

$app = new \Slim\Slim($config);

require_once('../factories.php');
require_once('../routes.php');
require_once('../errors.php');

$app->response->headers->set('Content-Type', 'application/json');
$app->run();

function runRoute(\Slim\Slim $app, $service)
{
    $result = $service->fetch($app->request->params('any'));
    $payload = $app->request->params('callback') . '(' . json_encode($result) . ')';
    $app->response->setBody($payload);
}