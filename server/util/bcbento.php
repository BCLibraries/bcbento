<?php

chdir(dirname(__DIR__)); // set directory to root
require 'vendor/autoload.php'; // composer autoload

$config = require 'config/.env.production.php';
$config['debug'] = false;

// convert all the command line arguments into a URL
$argv = $GLOBALS['argv'];
array_shift($GLOBALS['argv']);
$pathInfo = '/' . implode('/', $argv);

$app = new \Slim\Slim($config);

require_once 'factories.php';
require_once 'errors.php';

// Set up the environment so that Slim can route
$app->environment = \Slim\Environment::mock(
    [
        'PATH_INFO' => $pathInfo
    ]
);


// CLI-compatible not found error handler
$app->notFound(
    function () use ($app) {
        $url = $app->environment['PATH_INFO'];
        echo "Error: Cannot route to $url";
        $app->stop();
    }
);

// Format errors for CLI
$app->error(
    function (\Exception $e) use ($app) {
        echo $e;
        $app->stop();
    }
);

// routes - as per normal - no HTML though!
$app->get(
    '/download/portals',
    function () use ($app) {
        $client = new \Guzzle\Http\Client();
        $result = $client->get(
            'http://lgapi.libapps.com/1.1/guides?site_id=94&key=a8d4316f3140239e36f101209d9f1b36&group_ids=1214&status=1&search_terms=history'
        )->send()->getBody(true);
        $result_obj = json_decode($result);
        echo json_encode($result_obj, JSON_PRETTY_PRINT);
    }
);

$app->get(
    '/download/librarians',
    function () use ($app) {
        $client = new \Guzzle\Http\Client();
        $result = $client->get(
            'http://lgapi.libapps.com/1.1/accounts?site_id=94&key=a8d4316f3140239e36f101209d9f1b36'
        )->send()->getBody(true);
        $result_obj = json_decode($result);
        echo json_encode($result_obj, JSON_PRETTY_PRINT);
    }
);

$app->get(
    '/download/databases',
    function () use ($app) {
        $client = new \Guzzle\Http\Client();
        $result = $client->get(
            'http://lgapi.libapps.com/1.1/assets?site_id=94&key=a8d4316f3140239e36f101209d9f1b36&asset_types[]=10&asset_types[]=6'
        )->send()->getBody(true);
        $result_obj = json_decode($result);
        echo json_encode($result_obj, JSON_PRETTY_PRINT);
    }
);

$app->get(
    '/load/librarians',
    function () use ($app) {
        $json = file_get_contents('/Users/benjaminflorin/PhpstormProjects/bcbento-slim/server/librarians.json');
        $librarians = json_decode($json, true);
        $elasticsearch = new \Elasticsearch\Client(['hosts' => [$app->config('ELASTICSEARCH_HOST')]]);
        foreach ($librarians as $librarian) {
            $params = [];
            $params['id'] = $librarian['id'];
            unset($librarian['id']);
            $params['body'] = $librarian;
            $params['index'] = 'librarians';
            $params['type'] = 'librarian';
            $elasticsearch->index($params);
        }

    }
);

$app->get(
    '/load/portals',
    function () use ($app) {
        $json = file_get_contents('/Users/benjaminflorin/PhpstormProjects/bcbento-slim/server/libguides.json');
        $guides = json_decode($json, true);
        $elasticsearch = new \Elasticsearch\Client(['hosts' => [$app->config('ELASTICSEARCH_HOST')]]);
        foreach ($guides as $guide) {
            $params = [];
            $params['id'] = $guide['id'];
            unset($guide['id']);
            $params['body'] = $guide;
            $params['index'] = 'guides';
            $params['type'] = 'guide';
            $elasticsearch->index($params);
        }

    }
);

// run!
$app->run();