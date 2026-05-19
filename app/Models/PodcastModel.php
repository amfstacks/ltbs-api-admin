<?php

namespace App\Models;

use CodeIgniter\Model;

class PodcastModel extends Model
{
    protected $table            = 'podcasts';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true; // Protects data if accidentally deleted!
    
    protected $allowedFields    = [
        'title', 'slug', 'description', 'transcript', 'category_id', 'theme_id', 
        'media_high_url', 'media_low_url','master_high_url','master_low_url', 'cover_image_url', 'status', 'published_at', 'created_by','ffmeg_status','review_count'
    ];

    protected $useTimestamps = true;
}