<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddParentReplyId extends Migration
{
    public function up()
    {
        $this->forge->addColumn('forum_replies', [
            'parent_reply_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'thread_id' // Places it neatly in the DB
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('forum_replies', 'parent_reply_id');
    }
}