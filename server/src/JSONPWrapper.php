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
            $payload = $this->app->request->params('callback') . '(' . $payload . ')';
        }
        $this->app->response()->body($payload);
    }
}