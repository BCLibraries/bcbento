<?php

namespace BCLib\BCBento;

use Slim\Middleware;

class JSONPWrapper extends Middleware
{

    public function call()
    {
        $this->next->call();
        $payload = $this->app->response()->getBody();
        if ($this->app->request()->params('callback')) {
            $this->app->response->headers->set('Content-Type', 'application/javascript');
            $payload = $this->app->request->params('callback') . '(' . $payload . ')';
        }
        $this->app->response()->body($payload);
    }
}