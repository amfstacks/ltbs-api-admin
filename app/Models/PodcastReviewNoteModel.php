<?php
namespace App\Models;
use CodeIgniter\Model;

class PodcastReviewNoteModel extends Model {
    protected $table = 'podcast_review_notes';
    protected $primaryKey = 'id';
    protected $allowedFields = ['podcast_id', 'user_id', 'timestamp', 'note'];
    protected $useTimestamps = true;
    protected $updatedField = ''; // We don't need updated_at here
}