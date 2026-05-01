<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class BaseApiController extends ResourceController
{
    /**
     * Standardized Success Response
     * 
     * @param mixed $data The payload to send to the app
     * @param string $message A human-readable success message
     * @param int $statusCode HTTP Status Code (Default 200)
     */
    protected function sendSuccess($data = null, $message = 'Success', $statusCode = 200)
    {
        $response = [
            'status'  => 'success',
            'message' => $message,
        ];

        // Only attach the 'data' key if there is actual data to send
        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->respond($response, $statusCode);
    }

    /**
     * Standardized Error Response
     * 
     * @param string $message A human-readable error message
     * @param int $statusCode HTTP Status Code (Default 400 Bad Request)
     * @param mixed $errors Detailed validation errors (Optional)
     */
    protected function sendError($message = 'An error occurred', $statusCode = 400, $errors = null)
    {
        $response = [
            'status'  => 'error',
            'message' => $message,
        ];

        // Attach detailed validation errors (e.g., "Password must be 8 characters")
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $this->respond($response, $statusCode);
    }
}