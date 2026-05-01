<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AppKeyFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $headerKey = $request->getHeaderLine('X-App-Key');
        $validKey  = getenv('APP_API_KEY');

        if (empty($headerKey) || $headerKey !== $validKey) {
            $response = Services::response();
            $response->setStatusCode(401);
            $response->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid or missing App Key.'
            ]);
            return $response;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}