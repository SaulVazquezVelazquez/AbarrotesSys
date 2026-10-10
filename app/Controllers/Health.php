<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Health extends Controller
{
    public function index()
    {
        try {
            db_connect()->query('SELECT 1');
            return $this->response->setStatusCode(200)->setBody('ok');
        } catch (\Throwable $error) {
            log_message('error', 'Render health check failed: {message}', ['message' => $error->getMessage()]);
            return $this->response->setStatusCode(503)->setBody('unavailable');
        }
    }
}
