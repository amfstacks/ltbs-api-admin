<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function index()
    {
        // If already logged in, redirect to dashboard
        if (session()->get('is_logged_in')) {
            return redirect()->to('admin/dashboard');
        }

        return view('admin/auth/login', ['title' => 'Sign In']);
    }

    public function authenticate()
    {
        $userModel = new UserModel();
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $user = $userModel->where('email', $email)->first();

        // 1. Check if user exists and password is correct
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // 2. Check if they have admin privileges
            if (in_array($user['role'], ['superadmin', 'author','reviewer'])) {
                
                // 3. Check if account is active
                if ($user['status'] !== 'active') {
                    return redirect()->back()->with('error', 'Your account is currently disabled.');
                }

                // 4. Set Session Data
                $sessionData = [
                    'user_id'       => $user['id'],
                    'first_name'    => $user['first_name'],
                    'role'          => $user['role'],
                    'is_logged_in'  => true
                ];
                session()->set($sessionData);
// exit('a');
                return redirect()->to('admin/dashboard')->with('success', 'Welcome back, ' . $user['first_name']);
            }
        }

        return redirect()->back()->with('error', 'Invalid email or password.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('admin/login');
    }

    // Displays the Setup Password screen
    public function setupPassword($token)
    {
        $userModel = new \App\Models\UserModel();
        
        // We hash the URL token to compare it to the securely stored database token
        $hashedToken = hash('sha256', $token);

        // Find user with this token, ensuring it hasn't expired
        $user = $userModel->where('reset_token', $hashedToken)
                          ->where('reset_expires_at >=', date('Y-m-d H:i:s'))
                          ->first();

        if (!$user) {
            return redirect()->to('admin/login')->with('error', 'This invitation link is invalid or has expired. Please ask the administrator to send a new one.');
        }

        // Pass the raw token to the view so we can submit it in the form
        return view('admin/auth/setup_password', [
            'title' => 'Set Your Password',
            'token' => $token, 
            'user'  => $user
        ]);
    }

    // Processes the new password and activates the account
    public function savePassword()
    {
        $token = $this->request->getPost('token');
        $password = $this->request->getPost('password');
        $confirmPassword = $this->request->getPost('confirm_password');

        // 1. Basic Validation
        if (strlen($password) < 8) {
            return redirect()->back()->with('error', 'Password must be at least 8 characters long.');
        }
        if ($password !== $confirmPassword) {
            return redirect()->back()->with('error', 'Passwords do not match.');
        }

        // 2. Re-verify the token securely
        $userModel = new \App\Models\UserModel();
        $hashedToken = hash('sha256', $token);
        
        $user = $userModel->where('reset_token', $hashedToken)
                          ->where('reset_expires_at >=', date('Y-m-d H:i:s'))
                          ->first();

        if (!$user) {
            return redirect()->to('admin/login')->with('error', 'Security session expired. Please try the link again.');
        }

        // 3. Update the user, activate the account, and wipe the tokens!
        $userModel->update($user['id'], [
            'password_hash'    => password_hash($password, PASSWORD_BCRYPT),
            'status'           => 'active',
            'reset_token'      => null,
            'reset_expires_at' => null
        ]);

        return redirect()->to('admin/login')->with('success', 'Account activated! You can now sign in with your new password.');
    }
}