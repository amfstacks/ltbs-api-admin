<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePodcastTables extends Migration
{
    public function up()
    {
        // 1. Podcasts Table
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 255, 'unique' => true],
            'description'     => ['type' => 'TEXT'],
            'transcript'      => ['type' => 'LONGTEXT', 'null' => true],
            'category_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'theme_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'media_high_url'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // Cloudflare URL
            'media_low_url'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // Cloudflare Data-Saver URL
            'cover_image_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['draft', 'published', 'hidden'], 'default' => 'draft'],
            'published_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true], // Soft Deletes!
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('podcasts');

        // 2. Podcast Authors Pivot Table (For Co-Authors)
        $this->forge->addField([
            'podcast_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'author_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'is_primary' => ['type' => 'BOOLEAN', 'default' => 0],
            'can_edit'   => ['type' => 'BOOLEAN', 'default' => 0],
        ]);
        // Composite primary key
        $this->forge->addKey(['podcast_id', 'author_id'], true);
        $this->forge->createTable('podcast_authors');
    }

    public function down()
    {
        $this->forge->dropTable('podcast_authors');
        $this->forge->dropTable('podcasts');
    }
}