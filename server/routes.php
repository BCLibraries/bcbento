<?php

$paths = [
    '/typeahead',
    '/catalog',
    '/articles',
    '/librarians',
    '/guides',
    '/dpla',
    '/worldcat'
];

buildRoutes($paths, $app);

function buildRoutes($paths, \Slim\Slim $app)
{
    foreach ($paths as $path) {
        $service_name = ltrim($path, '/');
        $app->get(
            $path,
            function () use ($app, $service_name) {
                $service = $app->$service_name;
                runRoute($app, $service);
            }
        );
    }
}