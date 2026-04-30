<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UserController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        helper(['form', 'url', 'text']);
    }

    // 1. The Data Table
    public function index()
    {
        // Only Super Admins can access this!
        if (session()->get('role') !== 'superadmin') {
            return redirect()->to('admin/dashboard')->with('error', 'Unauthorized access.');
        }

        $data = [
            'title' => 'Team Management',
            // Fetch all users except pure app_users to keep the admin list clean
            'users' => $this->userModel->whereIn('role', ['superadmin', 'author'])->findAll()
        ];
        return view('admin/users/index', $data);
    }

    // 2. The Invite Form
    public function create()
    {
        $data = ['title' => 'Invite New Team Member'];
        return view('admin/users/form', $data);
    }

    // 3. Process the Invitation
    public function store()
    {
        // Validate email uniqueness
        $email = $this->request->getPost('email');
        if ($this->userModel->where('email', $email)->first()) {
            return redirect()->back()->withInput()->with('error', 'That email is already registered.');
        }

        // Generate a 40-character secure random token
        $token = bin2hex(random_bytes(20));

        $userData = [
            'first_name'       => $this->request->getPost('first_name'),
            'last_name'        => $this->request->getPost('last_name'),
            'email'            => $email,
            'role'             => $this->request->getPost('role'),
            'status'           => 'pending', // They haven't set a password yet!
            'reset_token'      => hash('sha256', $token), // Save the hashed version for security
            'reset_expires_at' => date('Y-m-d H:i:s', strtotime('+48 hours')), // Valid for 2 days
        ];

        $this->userModel->insert($userData);

        // --- EMAIL LOGIC ---
        // $inviteLink = site_url('admin/setup-password/' . $token);
        
        // $emailService = \Config\Services::email();
        // $emailService->setTo($email);
        // $emailService->setSubject('You have been invited to Let The Bible Speak');
        // $emailService->setMessage("Hello {$userData['first_name']},<br><br>You have been invited as an {$userData['role']}. Click the link below to set your password and activate your account:<br><br><a href='{$inviteLink}'>{$inviteLink}</a>");

        // if ($emailService->send()) {
        //     $msg = 'User invited and email sent successfully!';
        // } else {
        //     // Localhost fallback: Write the link to writable/logs/ so you can test it!
        //     log_message('error', "INVITE LINK FOR {$email}: " . $inviteLink);
        //     $msg = 'User created. (Local Mode: Check your CI4 Logs in writable/logs/ for the invite link!).';
        // }

        $inviteLink = site_url('admin/setup-password/' . $token);
        
        // Prepare the dynamic data for our beautiful email template
        $emailData = [
            'greeting'    => 'Welcome, ' . $userData['first_name'] . '!',
            'bodyMessage' => '<p>You have been invited to join the <strong>Let The Bible Speak</strong> administration team as an <strong>' . ucfirst($userData['role']) . '</strong>.</p><p>Please click the button below to securely set your password and activate your account.</p>',
            'buttonText'  => 'Set My Password',
            'buttonUrl'   => $inviteLink
        ];

        // Send it using our new 1-line MailService!
        $sent = \App\Libraries\MailService::send(
            $email, 
            'You are invited to Let The Bible Speak', 
            'emails/action', 
            $emailData
        );

        if ($sent) {
            $msg = 'User invited and invitation email sent successfully!';
        } else {
            $msg = 'User created, but the email failed to send. Check your SMTP settings.';
        }



        return redirect()->to('admin/users')->with('success', $msg);
    }
}