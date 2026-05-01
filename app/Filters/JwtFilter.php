<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');
        $token = null;

        // Extract the token from the "Bearer <token>" string
        if (!empty($header)) {
            if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
                $token = $matches[1];
            }
        }

        if (is_null($token) || empty($token)) {
            return $this->failUnauthorized('Access denied. No token provided.');
        }

        try {
            $key = getenv('JWT_SECRET');
            // Decode the token. If it's expired or tampered with, this throws an Exception!
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            
            // Optional: You can attach the user ID to the request so your controllers can access it easily
            // $request->user_id = $decoded->uid;

        } catch (Exception $ex) {
            return $this->failUnauthorized('Access denied. Token is invalid or expired.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }

    private function failUnauthorized($message)
    {
        $response = Services::response();
        $response->setStatusCode(401);
        $response->setJSON([
            'status'  => 'error',
            'message' => $message
        ]);
        return $response;
    }
}