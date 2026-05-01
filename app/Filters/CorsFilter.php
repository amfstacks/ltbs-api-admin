<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // 1. Allow any website/port to talk to this API (You can restrict this to your actual domain later)
        header('Access-Control-Allow-Origin: *');
        
        // 2. Explicitly whitelist the custom headers Flutter is sending
        header('Access-Control-Allow-Headers: X-App-Key, Content-Type, Authorization, Accept, Origin');
        
        // 3. Allow standard API methods
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');

        // 4. THE MAGIC BULLET: Handle Chrome's "Preflight" OPTIONS ping
        if ($request->getMethod() === 'OPTIONS' || $request->getMethod() === 'options') {
            // Chrome just wants to see the headers above. We exit immediately with a 200 OK so the real request can fire!
            header("HTTP/1.1 200 OK");
            exit(); 
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // We don't need to do anything after the request
    }
}