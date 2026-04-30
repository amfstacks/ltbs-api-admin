<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateForumReadsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'thread_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'last_read_at' => ['type' => 'DATETIME'],
        ]);
        // Unique key ensures we only have one record per user per thread
        $this->forge->addUniqueKey(['user_id', 'thread_id']);
        $this->forge->createTable('forum_thread_reads');
    }

    public function down()
    {
        $this->forge->dropTable('forum_thread_reads');
    }
}