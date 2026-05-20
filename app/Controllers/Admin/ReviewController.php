<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PodcastModel;
use App\Models\PodcastReviewModel;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class ReviewController extends BaseController
{
    public function index()
    {
        // $userId = session()->get('id');
        $userId = (int) session()->get('user_id');
        $userRole = session()->get('role');

        if (!in_array($userRole, ['reviewer', 'superadmin'])) {
            return redirect()->to('admin/dashboard')->with('error', 'Unauthorized access.');
        }

        $podcastModel = new PodcastModel();
        $reviewModel = new PodcastReviewModel();

        // ---------------------------------------------------------
        // 1. CALCULATE QUICK STATS
        // ---------------------------------------------------------
        
        // Awaiting Your Review: Podcasts 'in_review' where you haven't approved or rejected yet
        $awaitingQuery = $podcastModel->where('status', 'in_review')
            ->whereNotIn('id', function($builder) use ($userId) {
                return $builder->select('podcast_id')->from('podcast_reviews')->where('user_id', $userId);
            })->countAllResults();

        // Pending Others: You approved it, but it still has less than 3 total approvals
        $pendingOthersQuery = $reviewModel->select('podcast_reviews.id')
            ->join('podcasts', 'podcasts.id = podcast_reviews.podcast_id')
            ->where('podcast_reviews.user_id', $userId)
            ->where('podcast_reviews.status', 'approved')
            ->where('podcasts.review_count <', 3)
            ->where('podcasts.status', 'in_review')
            ->countAllResults();

        // Your Approved (Last 30 Days)
        $recentlyApprovedQuery = $reviewModel->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('updated_at >=', date('Y-m-d H:i:s', strtotime('-30 days')))
            ->countAllResults();

        // ---------------------------------------------------------
        // 2. FETCH THE QUEUE (With User's Specific Status)
        // ---------------------------------------------------------
        $podcasts = $podcastModel->select('podcasts.*, users.first_name, users.last_name, podcast_reviews.status as my_review_status')
            ->join('users', 'users.id = podcasts.created_by', 'left')
            // Left join on reviews so we know if the logged-in user has already acted on it
            ->join('podcast_reviews', 'podcast_reviews.podcast_id = podcasts.id AND podcast_reviews.user_id = ' . $userId, 'left')
            ->where('podcasts.status', 'in_review')
            ->where('podcasts.ffmeg_status', 'completed')
            ->orderBy('podcasts.created_at', 'ASC') // Oldest first (FIFO queue system!)
            ->findAll();

        $data = [
            'title'                 => 'Review Queue',
            'awaiting_count'        => $awaitingQuery,
            'pending_others_count'  => $pendingOthersQuery,
            'recent_approved_count' => $recentlyApprovedQuery,
            'podcasts'              => $podcasts
        ];

        return view('admin/reviews/index', $data);
    }

    public function process($id)
    {
        $userId = (int) session()->get('user_id');
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) {
            return redirect()->to('admin/dashboard')->with('error', 'Unauthorized access.');
        }

        $podcastModel = new PodcastModel();
        $reviewModel = new \App\Models\PodcastReviewModel();
        $db = \Config\Database::connect();

        // 1. Fetch Podcast
        $podcast = $podcastModel->select('podcasts.*, categories.name as category_name')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->find($id);

        if (!$podcast) {
            return redirect()->to('admin/reviews')->with('error', 'Podcast not found.');
        }

        // 2. Fetch Authors
        $authors = $db->table('podcast_authors')
            ->select('users.first_name, users.last_name')
            ->join('users', 'users.id = podcast_authors.author_id')
            ->where('podcast_authors.podcast_id', $id)
            ->get()->getResultArray();

        // 3. Fetch Consensus (All reviews for this podcast)
        $reviews = $reviewModel->select('podcast_reviews.*, users.first_name, users.last_name, users.role')
            ->join('users', 'users.id = podcast_reviews.user_id')
            ->where('podcast_id', $id)
            ->findAll();

        // 4. Check if Current User has a pending review to allow them to vote
        $myReview = array_filter($reviews, fn($r) => $r['user_id'] == $userId);
        $myReviewStatus = !empty($myReview) ? array_values($myReview)[0]['status'] : 'pending';

        // 5. Generate Secure URLs for the Player (Assumes BaseController has getSecureAudioUrl)
        $highUrl = $this->getSecureAudioUrl($podcast['master_high_url']);
        $lowUrl = $this->getSecureAudioUrl($podcast['master_low_url'] ?? $podcast['master_high_url']);

        $fallbackAudio = 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3';

        // Use !empty() instead of ?? to catch empty strings
        $highUrl = !empty($podcast['master_high_url']) 
            ? $this->getSecureAudioUrl($podcast['master_high_url']) 
            : $fallbackAudio;

        $lowUrl = !empty($podcast['master_low_url']) 
            ? $this->getSecureAudioUrl($podcast['master_low_url']) 
            : null;
// $r2PublicUrl = rtrim(getenv('R2_PUBLIC_URL'), '/');
//             $highUrl = !empty($podcast['master_high_url']) 
//             ? $r2PublicUrl . '/' . $podcast['master_high_url']
//             : $fallbackAudio;

//         $lowUrl = !empty($podcast['master_low_url']) 
//             ? $r2PublicUrl . '/' . $podcast['master_low_url']
//             : null;
$mentionableUsers = [];
        foreach($reviews as $r) {
            $mentionableUsers[] = ['id' => $r['user_id'], 'name' => $r['first_name'] . ' ' . $r['last_name']];
        }
        $data = [
            'title'     => 'Review Room',
            'podcast'   => $podcast,
            'authors'   => $authors,
            'reviews'   => $reviews,
            'myStatus'  => $myReviewStatus,
            'highUrl'   => $highUrl,
            'lowUrl'    => $lowUrl,
            'mentionableUsers' => json_encode($mentionableUsers)
        ];

        return view('admin/reviews/process', $data);
    }

     protected function getSecureAudioUrl($rawUrl)
    {
        if (empty($rawUrl)) return null;

        // 1. Extract the exact file path from the URL
        // E.g., "https://pub-mycdn.r2.dev/audio/message.mp3" -> "audio/message.mp3"
        $parsedUrl = parse_url($rawUrl);
        $objectKey = ltrim($parsedUrl['path'], '/');

        try {
            // 2. Initialize the S3 Client for Cloudflare R2
            $s3Client = new S3Client([
                'region'      => 'auto', // R2 always uses 'auto'
                'endpoint'    => getenv('R2_ENDPOINT'),
                'version'     => 'latest',
                'credentials' => [
                    'key'    => getenv('R2_ACCESS_KEY'),
                    'secret' => getenv('R2_SECRET_KEY'),
                ],
            ]);

            // 3. Create the command to fetch the file
            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => getenv('R2_BUCKET'),
                'Key'    => $objectKey,
            ]);

            // 4. Generate the signed URL valid for 2 hours (+2 hours)
            $request = $s3Client->createPresignedRequest($cmd, '+2 hours');

            // Return the secure, time-expiring URL!
            return (string) $request->getUri();
            
        } catch (AwsException $e) {
            // If Cloudflare is down or keys are wrong, log it so you can debug,
            // but don't crash the entire API response.
            log_message('error', '[R2 URL Signing Failed] ' . $e->getMessage());
            return null;
        }}


        // =======================================================
    // AJAX ENDPOINTS
    // =======================================================

    public function getNotes($podcastId)
    {
        $noteModel = new \App\Models\PodcastReviewNoteModel();
        $notes = $noteModel->select('podcast_review_notes.*, users.first_name, users.role')
            ->join('users', 'users.id = podcast_review_notes.user_id')
            ->where('podcast_id', $podcastId)
            ->orderBy('timestamp', 'ASC') // Order by audio time!
            ->findAll();
            
        return $this->response->setJSON($notes);
    }

    public function addNote($podcastId)
    {
        $noteModel = new \App\Models\PodcastReviewNoteModel();
        $json = $this->request->getJSON();
        
        $noteModel->insert([
            'podcast_id' => $podcastId,
            'user_id'    => session()->get('user_id'),
            'timestamp'  => (int)$json->timestamp,
            'note'       => esc($json->note)
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function getChats_old($podcastId)
    {
        // Only Reviewers and Admins can fetch chats
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) {
            return $this->response->setStatusCode(403);
        }

        $chatModel = new \App\Models\PodcastReviewChatModel();
        $chats = $chatModel->select('podcast_review_chats.*, users.first_name, users.last_name')
            ->join('users', 'users.id = podcast_review_chats.user_id')
            ->where('podcast_id', $podcastId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        return $this->response->setJSON(['chats' => $chats, 'me' => session()->get('user_id')]);
    }

    public function addChat_old($podcastId)
    {
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) return $this->response->setStatusCode(403);

        $json = $this->request->getJSON();
        (new \App\Models\PodcastReviewChatModel())->insert([
            'podcast_id' => $podcastId,
            'user_id'    => session()->get('user_id'),
            'message'    => esc($json->message)
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }
    public function getChats($podcastId)
    {
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) return $this->response->setStatusCode(403);

        $db = \Config\Database::connect();
        
        // We use a self-join to grab the parent message snippet if it's a reply!
        $chats = $db->table('podcast_review_chats c1')
            ->select('c1.*, users.first_name, users.last_name, c2.message as reply_to_message, u2.first_name as reply_to_name')
            ->join('users', 'users.id = c1.user_id')
            ->join('podcast_review_chats c2', 'c2.id = c1.reply_to_id', 'left')
            ->join('users u2', 'u2.id = c2.user_id', 'left')
            ->where('c1.podcast_id', $podcastId)
            ->orderBy('c1.created_at', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON(['chats' => $chats, 'me' => session()->get('user_id')]);
    }

    public function addChat($podcastId)
    {
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) return $this->response->setStatusCode(403);

        $json = $this->request->getJSON();
        (new \App\Models\PodcastReviewChatModel())->insert([
            'podcast_id'  => $podcastId,
            'user_id'     => session()->get('user_id'),
            'reply_to_id' => !empty($json->reply_to_id) ? $json->reply_to_id : null,
            'message'     => esc($json->message)
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function submitDecision_old($podcastId)
    {
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) return $this->response->setStatusCode(403);

        $json = $this->request->getJSON();
        $decision = $json->decision; // 'approved', 'changes_requested', 'rejected'
        $userId = session()->get('user_id');

        $reviewModel = new \App\Models\PodcastReviewModel();
        $podcastModel = new \App\Models\PodcastModel();

        // 1. Upsert the Review Ledger
        $existing = $reviewModel->where(['podcast_id' => $podcastId, 'user_id' => $userId])->first();
        if ($existing) {
            $reviewModel->update($existing['id'], ['status' => $decision]);
        } else {
            $reviewModel->insert(['podcast_id' => $podcastId, 'user_id' => $userId, 'status' => $decision]);
        }

        // 2. Recalculate global approvals for this podcast
        $totalApprovals = $reviewModel->where(['podcast_id' => $podcastId, 'status' => 'approved'])->countAllResults();
        
        // 3. Update the podcast table cache
        $updateData = ['review_count' => $totalApprovals];
        
        // AUTO-PUBLISH LOGIC: If it hits 3 approvals, automatically push it live!
        if ($totalApprovals >= 3) {
            $updateData['status'] = 'published';
            $updateData['published_at'] = date('Y-m-d H:i:s');
        }

        $podcastModel->update($podcastId, $updateData);

        return $this->response->setJSON([
            'status' => 'success',
            'total_approvals' => $totalApprovals,
            'is_published' => ($totalApprovals >= 3)
        ]);
    }
    public function submitDecision($podcastId)
    {
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) return $this->response->setStatusCode(403);

        $json = $this->request->getJSON();
        $decision = $json->decision; // 'approved', 'changes_requested', 'rejected'
        // 👉 NEW: Grab the notes from the payload
        $notes = isset($json->notes) ? esc($json->notes) : null; 
        
        $userId = session()->get('user_id');

        $reviewModel = new \App\Models\PodcastReviewModel();
        $podcastModel = new \App\Models\PodcastModel();

        // 1. Upsert the Review Ledger (NOW WITH NOTES)
        $existing = $reviewModel->where(['podcast_id' => $podcastId, 'user_id' => $userId])->first();
        if ($existing) {
            $reviewModel->update($existing['id'], ['status' => $decision, 'notes' => $notes]);
        } else {
            $reviewModel->insert(['podcast_id' => $podcastId, 'user_id' => $userId, 'status' => $decision, 'notes' => $notes]);
        }

        // 2. Recalculate global approvals for this podcast
        $totalApprovals = $reviewModel->where(['podcast_id' => $podcastId, 'status' => 'approved'])->countAllResults();
        
        // 3. Update the podcast table cache
        $updateData = ['review_count' => $totalApprovals];
        
        // AUTO-PUBLISH LOGIC
        if ($totalApprovals >= 3) {
            $updateData['status'] = 'published';
            $updateData['published_at'] = date('Y-m-d H:i:s');
        }

        $podcastModel->update($podcastId, $updateData);

        return $this->response->setJSON([
            'status' => 'success',
            'total_approvals' => $totalApprovals,
            'is_published' => ($totalApprovals >= 3)
        ]);
    }
    public function history()
    {
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) return redirect()->to('admin/dashboard');

        $userId = (int) session()->get('user_id');
        $db = \Config\Database::connect();

        // Fetch all podcasts this specific user has reviewed
        $history = $db->table('podcast_reviews')
            ->select('podcast_reviews.status as my_decision, podcast_reviews.updated_at as decision_date, podcasts.title, podcasts.status as global_status')
            ->join('podcasts', 'podcasts.id = podcast_reviews.podcast_id')
            ->where('podcast_reviews.user_id', $userId)
            ->where('podcast_reviews.status !=', 'pending')
            ->orderBy('podcast_reviews.updated_at', 'DESC')
            ->get()->getResultArray();

        return view('admin/reviews/history', ['title' => 'Review History', 'history' => $history]);
    }

    public function guidelines()
    {
        if (!in_array(session()->get('role'), ['reviewer', 'superadmin'])) return redirect()->to('admin/dashboard');
        
        // This is a static reference page, no DB call needed!
        return view('admin/reviews/guidelines', ['title' => 'QA Guidelines']);
    }
}