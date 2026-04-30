<?php
namespace App\Models;
use CodeIgniter\Model;

class ForumReplyModel extends Model
{
    protected $table          = 'forum_replies';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields  = ['thread_id','parent_reply_id', 'user_id', 'message'];
    protected $useTimestamps  = true;
}