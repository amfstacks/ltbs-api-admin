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

    // =========================================================================
    // USER PROFILE MANAGEMENT
    // =========================================================================

    public function profile()
    {
        $userId = session()->get('user_id');
        
        $data = [
            'title' => 'My Profile',
            'user'  => $this->userModel->find($userId)
        ];
        
        return view('admin/users/profile', $data);
    }

    public function updateProfile()
    {
        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);

        if (!$user) {
            return redirect()->to('admin/dashboard')->with('error', 'User not found.');
        }

        $saveData = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name'  => $this->request->getPost('last_name'),
            'bio'        => $this->request->getPost('bio'), // WYSIWYG HTML content
        ];

        // --------------------------------------------------------------------
        // CLOUDFLARE R2: Handle Profile Picture using our DRY WebP Optimizer
        // --------------------------------------------------------------------
        $file = $this->request->getFile('profile_image');
        if ($file && $file->isValid()) {
            
            $cloudflare = new \App\Libraries\CloudflareStorage();
            
            // Generate a safe, unique slug for the user's image
            $slug = 'user_' . $userId . '_' . time(); 
            $oldUrl = $user['profile_image_url'] ?? null;
            
            $imageUrl = $cloudflare->optimizeAndUpload($file, 'users/profiles', $slug, $oldUrl);
            
            if ($imageUrl) {
                $saveData['profile_image_url'] = $imageUrl;
                
                // 👉 CRITICAL: Update the session so the top navigation bar avatar changes instantly!
                session()->set('profile_image_url', $imageUrl); 
            } else {
                return redirect()->back()->withInput()->with('error', 'Failed to optimize and upload profile picture.');
            }
        }

        // Update the database
        $this->userModel->update($userId, $saveData);
        
        // Update session names in case they fixed a typo in their name
        session()->set('first_name', $saveData['first_name']);
        session()->set('last_name', $saveData['last_name']);

        return redirect()->to('admin/profile')->with('success', 'Profile updated successfully.');
    }

    // =========================================================================
    // PASSWORD MANAGEMENT
    // =========================================================================

    public function changePassword()
    {
        $data = ['title' => 'Change Password'];
        return view('admin/users/change_password', $data);
    }

    public function updatePassword()
    {
        $userId = session()->get('user_id');
        $user = $this->userModel->find($userId);

        if (!$user) {
            return redirect()->to('admin/dashboard')->with('error', 'User not found.');
        }

        $oldPassword     = $this->request->getPost('old_password');
        $newPassword     = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        // 1. Verify Old Password
        if (!password_verify($oldPassword, $user['password_hash'])) {
            return redirect()->back()->with('error', 'Your current password is incorrect.');
        }

        // 2. Validate New Password Length
        if (strlen($newPassword) < 8) {
            return redirect()->back()->with('error', 'Your new password must be at least 8 characters long.');
        }

        // 3. Confirm Passwords Match
        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Your new passwords do not match.');
        }

        // 4. Hash and Save
        $this->userModel->update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => 12])
        ]);

        return redirect()->to('admin/change-password')->with('success', 'Your password has been securely updated!');
    }
    
}