<?php

register_shutdown_function("fatal_handler");

function fatal_handler()
{
    $errfile = "unknown file";
    $errstr = "shutdown";
    $errno = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

    if ($error !== null) {
        $errno = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr = $error["message"];
        error_log("$errno \t $errfile \t $errline \t $errstr");
    }

}

$app->notFound(
    function () use ($app) {
        $app->halt(404, json_encode(['status' => '404', 'message' => 'not found']));
    }
);