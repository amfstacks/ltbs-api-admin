<?php

namespace App\Controllers\Api;

use App\Models\UserModel;

class ProfileController extends BaseApiController
{
    /**
     * GET /api/v1/profile
     * Fetches the user's details, preferences, and community stats.
     */
    public function index()
    {
        $userId = $this->getUserId();
        if (!$userId) return $this->sendError('Unauthorized', 401);

        $db = \Config\Database::connect();
        $userModel = new UserModel();

        // 1. Fetch User Data
        $user = $userModel->find($userId);
        if (!$user) return $this->sendError('User not found', 404);

        // Remove sensitive data before sending to Flutter
        unset($user['password']);

        // 2. Fetch Gamification Stats!
        $listenCount = $db->table('listen_history')->where('user_id', $userId)->countAllResults();
        
        $threadCount = $db->table('forum_threads')->where('user_id', $userId)->where('deleted_at', null)->countAllResults();
        $replyCount = $db->table('forum_replies')->where('user_id', $userId)->where('deleted_at', null)->countAllResults();

        // 3. Assemble the Payload
        $payload = [
            'user' => [
                'id'                 => (int)$user['id'],
                'first_name'         => $user['first_name'],
                'last_name'          => $user['last_name'],
                'email'              => $user['email'],
                'bio'                => $user['bio'],
                'profile_image_url'  => $user['profile_image_url'],
                'is_dark_mode'       => (bool)$user['is_dark_mode'],
                'is_data_saver_on'   => (bool)$user['is_data_saver_on'],
                'push_notifications' => (bool)$user['push_notifications'],
                'joined_at'          => $user['created_at'],
                'has_password'       => !empty($user['password_hash']),
            ],
            'stats' => [
                'total_listens'       => $listenCount,
                'forum_contributions' => $threadCount + $replyCount
            ]
        ];

        return $this->sendSuccess($payload, 'Profile loaded successfully');
    }

   
    /**
     * POST /api/v1/profile/update
     * Updates text fields and toggle preferences gracefully from JSON.
     */
    public function updateProfile()
    {
        $userId = $this->getUserId();
        if (!$userId) return $this->sendError('Unauthorized', 401);

        // 1. Grab the raw JSON sent from Flutter's ApiClient
        $input = $this->request->getJSON(true) ?? $this->request->getVar() ?? [];

        $rules = [
            'first_name'         => 'permit_empty|min_length[2]|max_length[50]',
            'last_name'          => 'permit_empty|min_length[2]|max_length[50]',
            'bio'                => 'permit_empty|max_length[160]',
            // Note: We removed the 'in_list' rule for booleans because Flutter sends 
            // raw JSON booleans (true/false) which can trip up CI4's string validation.
        ];

        // Pass the $input explicitly to the validator so it checks the JSON data
        if (!$this->validateData($input, $rules)) {
            return $this->sendError('Validation failed', 400, $this->validator->getErrors());
        }

        $updateData = [];

        // 2. Handle Text Fields (Names, Bio)
        $allowedFields = ['first_name', 'last_name', 'bio'];
        foreach ($allowedFields as $field) {
            // Check if Flutter explicitly sent this field in the JSON payload
            if (array_key_exists($field, $input)) {
                $updateData[$field] = trim($input[$field]);
            }
        }

        // 3. Handle Boolean Toggles (Dark Mode, Data Saver, Push)
        $toggles = ['is_dark_mode', 'is_data_saver_on', 'push_notifications'];
        foreach ($toggles as $toggle) {
            if (array_key_exists($toggle, $input)) {
                $val = $input[$toggle];
                // FILTER_VALIDATE_BOOLEAN safely converts JSON true, "true", or 1 into a valid boolean, then we cast to 1 or 0 for MySQL
                $updateData[$toggle] = filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }

        // 4. Failsafe
        if (empty($updateData)) {
            return $this->sendError('No data provided to update', 400);
        }

        // 5. Update the Database
        $userModel = new UserModel();
        $userModel->update($userId, $updateData);

        return $this->sendSuccess(null, 'Profile updated successfully');
    }

    /**
     * POST /api/v1/profile/avatar
     * Accepts a Multipart File Upload from Flutter, saves it, and updates DB.
     */
    public function uploadAvatar()
    {
        $userId = $this->getUserId();
        if (!$userId) return $this->sendError('Unauthorized', 401);

        // Validate the uploaded file is actually an image and under 2MB
        $validationRule = [
            'avatar' => [
                'label' => 'Avatar Image',
                'rules' => 'uploaded[avatar]|is_image[avatar]|mime_in[avatar,image/jpg,image/jpeg,image/png,image/webp]|max_size[avatar,2048]',
            ],
        ];

        if (!$this->validate($validationRule)) {
            return $this->sendError('Invalid image', 400, $this->validator->getErrors());
        }

        $file = $this->request->getFile('avatar');

        if (!$file->hasMoved()) {
            // Generate a secure, random file name so users can't overwrite each other
            $newName = $file->getRandomName();
            
            // Move it to your server's public upload folder first
            $file->move(FCPATH . 'uploads/avatars', $newName);

            $localFilePath = FCPATH . 'uploads/avatars/' . $newName;
            $publicUrl = base_url('uploads/avatars/' . $newName);

            // =========================================================
            // PRO TIP: CLOUDFLARE R2 INTEGRATION GOES HERE!
            // If you are using AWS S3 SDK for Cloudflare R2:
            // 
            // 1. $s3Client->putObject([ ... 'SourceFile' => $localFilePath ]);
            // 2. $publicUrl = 'https://pub-yourcloudflare.r2.dev/avatars/' . $newName;
            // 3. unlink($localFilePath); // Delete local file after cloud upload
            // =========================================================

            // Update the user's profile with the new URL
            $userModel = new UserModel();
            $userModel->update($userId, ['profile_image_url' => $publicUrl]);

            return $this->sendSuccess(['profile_image_url' => $publicUrl], 'Avatar uploaded successfully');
        }

        return $this->sendError('Could not move the file.', 500);
    }


    /**
     * POST /api/v1/profile/password
     */
    public function updatePassword()
    {
        $userId = $this->getUserId();
        if (!$userId) return $this->sendError('Unauthorized', 401);

        $input = $this->request->getJSON(true) ?? $this->request->getVar();
        $oldPassword = $input['old_password'] ?? null;
        $newPassword = $input['new_password'] ?? null;

        if (!$newPassword || strlen($newPassword) < 8) {
            return $this->sendError('New password must be at least 8 characters', 400);
        }

        $userModel = new UserModel();
        $user = $userModel->find($userId);

        // If they already have a password, they MUST provide the correct old one
        if (!empty($user['password_hash'])) {
            if (!$oldPassword || !password_verify($oldPassword, $user['password_hash'])) {
                return $this->sendError('Incorrect current password', 400);
            }
        }

        // Save the new password
        $userModel->update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);

        return $this->sendSuccess(null, 'Password updated successfully');
    }
}