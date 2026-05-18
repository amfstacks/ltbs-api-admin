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

        $data = [
            'title'     => 'Review Room',
            'podcast'   => $podcast,
            'authors'   => $authors,
            'reviews'   => $reviews,
            'myStatus'  => $myReviewStatus,
            'highUrl'   => $highUrl,
            'lowUrl'    => $lowUrl,
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
}