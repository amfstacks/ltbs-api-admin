<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use Firebase\JWT\JWT;

class AuthController extends BaseApiController
{
    /**
     * Generate a JWT for the authenticated user
     */
    private function generateJWT($user)
    {
        $key = getenv('JWT_SECRET');
        $payload = [
            'iat' => time(), // Issued at
            'exp' => time() + (86400 * 30), // Expires in 30 days
            'uid' => $user['id'], // User ID
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return JWT::encode($payload, $key, 'HS256');
    }

    /**
     * POST /api/v1/auth/register
     * Register a new user
     */
    public function register()
    {
        $rules = [
            'first_name' => 'required|min_length[2]|max_length[50]',
            'last_name'  => 'required|min_length[2]|max_length[50]',
            'email'      => 'required|valid_email|is_unique[users.email]',
            'password'   => 'required|min_length[8]'
        ];

        // 1. Validate the input
        if (!$this->validate($rules)) {
            return $this->sendError('Validation failed', 400, $this->validator->getErrors());
        }

        $userModel = new UserModel();

        // 2. Prepare the data (Hash the password!)
        $userData = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name'  => $this->request->getPost('last_name'),
            'email'      => $this->request->getPost('email'),
            'password_hash'   => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role'       => 'user', // Default role for app users
            'status'     => 'active'
        ];

        // 3. Insert the user into the database
        $userId = $userModel->insert($userData);

        if (!$userId) {
            return $this->sendError('Failed to create account. Please try again.', 500);
        }

        // 4. Fetch the newly created user to generate their token
        $user = $userModel->find($userId);
        
        // Remove the password hash before sending user data back
        unset($user['password_hash']);

        // 5. Generate the JWT
        $token = $this->generateJWT($user);

        // 6. Return standard success response
        $responseData = [
            'user'  => $user,
            'token' => $token
        ];

        return $this->sendSuccess($responseData, 'Account created successfully', 201);
    }

    /**
     * POST /api/v1/auth/login
     * Authenticate a user and return a JWT
     */
    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->sendError('Validation failed', 400, $this->validator->getErrors());
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        // 1. Check if user exists and password matches
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->sendError('Invalid email or password', 401);
        }

        // 2. Check if the account is active
        if ($user['status'] !== 'active') {
            return $this->sendError('Your account has been deactivated. Please contact support.', 403);
        }

        // Remove the password hash before sending data back
        unset($user['password']);

        // 3. Generate the JWT
        $token = $this->generateJWT($user);

        // 4. Return standard success response
        $responseData = [
            'user'  => $user,
            'token' => $token
        ];

        return $this->sendSuccess($responseData, 'Login successful');
    }

    /**
     * POST /api/v1/auth/logout
     * "Log out" the user. 
     * Note: With JWT, true logout requires the client (Flutter) to delete the token.
     * We just return a success message confirming they initiated the process.
     */
    public function logout()
    {
        return $this->sendSuccess(null, 'Successfully logged out');
    }
}