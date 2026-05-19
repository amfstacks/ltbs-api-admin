<?php
namespace App\Models;
use CodeIgniter\Model;

class PodcastReviewChatModel extends Model {
    protected $table = 'podcast_review_chats';
    protected $primaryKey = 'id';
    protected $allowedFields = ['podcast_id', 'user_id','reply_to_id', 'message'];
    protected $useTimestamps = true;
    protected $updatedField = '';
}