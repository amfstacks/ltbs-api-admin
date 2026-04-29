<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTaxonomyTables extends Migration
{
    public function up()
    {
        // 1. Categories Table
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'icon_url'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'unsigned' => true], // 1: Active, 0: Inactive
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('categories');

        // 2. Themes Table
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'icon_url'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'unsigned' => true], // 1: Active, 0: Inactive
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('themes');
    }

    public function down()
    {
        $this->forge->dropTable('categories');
        $this->forge->dropTable('themes');
    }
}