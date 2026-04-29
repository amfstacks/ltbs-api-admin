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
            if (in_array($user['role'], ['superadmin', 'author'])) {
                
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
}