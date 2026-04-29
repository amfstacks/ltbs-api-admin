<?php

namespace App\Models;

use CodeIgniter\Model;

class PodcastAuthorModel extends Model
{
    protected $table         = 'podcast_authors';
    protected $returnType    = 'array';
    protected $allowedFields = ['podcast_id', 'author_id', 'is_primary', 'can_edit'];
}