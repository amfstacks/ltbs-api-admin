<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateForumTables extends Migration
{
    public function up()
    {
        // 1. Threads Table (The main question/topic)
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'podcast_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], // The App User who asked
            'title'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'status'     => ['type' => 'ENUM', 'constraint' => ['open', 'resolved', 'closed'], 'default' => 'open'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('forum_threads');

        // 2. Replies Table (The timeline of answers)
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'thread_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true], // Could be App User OR Admin/Author
            'message'    => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('forum_replies');
    }

    public function down()
    {
        $this->forge->dropTable('forum_replies');
        $this->forge->dropTable('forum_threads');
    }
}