<?php

namespace App\Models;

use CodeIgniter\Model;

class PodcastReviewModel extends Model
{
    protected $table            = 'podcast_reviews';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['podcast_id', 'user_id', 'status','notes'];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}