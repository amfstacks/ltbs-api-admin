<?php
namespace App\Models;
use CodeIgniter\Model;

class ForumThreadModel extends Model
{
    protected $table          = 'forum_threads';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['podcast_id', 'user_id', 'title', 'status'];
    protected $useTimestamps  = true;
}