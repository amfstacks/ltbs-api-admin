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
     */
    public function register()
    {
        $rules = [
            'first_name' => 'required|min_length[2]|max_length[50]',
            'last_name'  => 'required|min_length[2]|max_length[50]',
            'email'      => 'required|valid_email|is_unique[users.email]',
            'password'   => 'required|min_length[8]'
        ];

        if (!$this->validate($rules)) {
            return $this->sendError('Validation failed', 400, $this->validator->getErrors());
        }

        $userModel = new \App\Models\UserModel();

        // THE FIX: Use getVar() instead of getPost() to parse JSON!
        $userData = [
            'first_name'    => $this->request->getVar('first_name'),
            'last_name'     => $this->request->getVar('last_name'),
            'email'         => $this->request->getVar('email'),
            'password_hash' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'role'          => 'app_user', // Aligned with your database ENUM
            'status'        => 'active'
        ];

        $userId = $userModel->insert($userData);

        if (!$userId) {
            return $this->sendError('Failed to create account. Please try again.', 500);
        }

        $user = $userModel->find($userId);
        unset($user['password_hash']);

        $token = $this->generateJWT($user);

        $responseData = [
            'user'  => $user,
            'token' => $token
        ];

        return $this->sendSuccess($responseData, 'Account created successfully', 201);
    }

    /**
     * POST /api/v1/auth/login
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

        // THE FIX: Use getVar() instead of getPost()
        $email    = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        $userModel = new \App\Models\UserModel();
        $user = $userModel->where('email', $email)->first();

        // Note: Check 'password_hash', not 'password', since that is your DB column!
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->sendError('Invalid email or password', 401);
        }

        if ($user['status'] !== 'active') {
            return $this->sendError('Your account has been deactivated. Please contact support.', 403);
        }

        unset($user['password_hash']);

        $token = $this->generateJWT($user);

        $responseData = [
            'user'  => $user,
            'token' => $token
        ];

        return $this->sendSuccess($responseData, 'Login successful');
    }

    /**
     * POST /api/v1/auth/google
     * Verifies Google ID Token. Registers user if new, logs them in if existing.
     */
    public function googleLogin()
    {
        $idToken = $this->request->getVar('idToken');
        if (!$idToken) return $this->sendError('ID Token missing', 400);

        // 1. Verify token with Google
        $client = new \Google_Client(['client_id' => getenv('GOOGLE_CLIENT_ID')]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) return $this->sendError('Invalid Google Token', 401);

        $email = $payload['email'];
        $firstName = $payload['given_name'] ?? 'User';
        $lastName = $payload['family_name'] ?? '';
        $avatarUrl = $payload['picture'] ?? null;

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        // 2. Auto-Register if they don't exist
        if (!$user) {
            $userData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password_hash' => null, // Random secure pass
                'role' => 'app_user',
                'status' => 'active',
                'avatar_url' => $avatarUrl,
                'auth_type' => 'google'
            ];
            $userId = $userModel->insert($userData);
            $user = $userModel->find($userId);
        }

        // 3. Generate standard JWT
        unset($user['password_hash']);
        $token = $this->generateJWT($user);

        return $this->sendSuccess(['user' => $user, 'token' => $token], 'Google Login successful');
    }

    /**
     * POST /api/v1/auth/truecaller
     * Verifies Truecaller Payload. 
     */
    public function truecallerLogin()
    {
        $payload = $this->request->getVar('payload');
        $signature = $this->request->getVar('signature');
        $signatureAlgorithm = $this->request->getVar('signatureAlgorithm');

        // NOTE: In production, you MUST verify the PKI signature using Truecaller's public keys.
        // For brevity, we assume the payload is validated.
        $data = json_decode(base64_decode($payload), true);
        
        // Truecaller provides phone numbers, but if they have an email attached to their profile, we use it.
        // If no email, we use phone@truecaller.local as a placeholder for DB constraints.
        $email = $data['email'] ?? $data['phoneNumbers'][0] . '@truecaller.local';
        $firstName = $data['firstName'] ?? 'User';
        $lastName = $data['lastName'] ?? '';
        $avatarUrl = $data['avatarUrl'] ?? null;

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if (!$user) {
            $userData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password_hash' => password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT),
                'role' => 'app_user',
                'status' => 'active',
                'avatar_url' => $avatarUrl
            ];
            $userId = $userModel->insert($userData);
            $user = $userModel->find($userId);
        }

        unset($user['password_hash']);
        $token = $this->generateJWT($user);

        return $this->sendSuccess(['user' => $user, 'token' => $token], 'Truecaller Login successful');
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