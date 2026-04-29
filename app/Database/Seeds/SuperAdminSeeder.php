<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'first_name'    => 'System',
            'last_name'     => 'Admin',
            'email'         => 'admin@letthebiblespeak.com',
            'password_hash' => password_hash('Admin@123!', PASSWORD_BCRYPT), // Secure hash!
            'role'          => 'superadmin',
            'status'        => 'active',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        // Insert into the users table
        $this->db->table('users')->insert($data);
    }
}
