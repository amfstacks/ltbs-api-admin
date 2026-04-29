<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'first_name'    => ['type' => 'VARCHAR', 'constraint' => '100'],
            'last_name'     => ['type' => 'VARCHAR', 'constraint' => '100'],
            'email'         => ['type' => 'VARCHAR', 'constraint' => '255', 'unique' => true],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => '255'],
            'role'          => ['type' => 'ENUM', 'constraint' => ['superadmin', 'author', 'app_user'], 'default' => 'app_user'],
            'status'        => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'pending'], 'default' => 'pending'],
            'bio'           => ['type' => 'TEXT', 'null' => true],
            'avatar_url'    => ['type' => 'VARCHAR', 'constraint' => '255', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true], // Soft Deletes!
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}