<?php

$app->notFound(
    function () use ($app) {
        $app->halt(404, json_encode(['status' => '404', 'message' => 'not found']));
    }
);