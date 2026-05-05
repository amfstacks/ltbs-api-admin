<?php

namespace App\Controllers\Api;

use App\Models\PodcastModel;
use App\Models\ForumThreadModel;
use App\Models\ForumReplyModel;

class ForumController extends BaseApiController
{
    /**
     * Helper to get the logged-in User ID from the JWT request header
     */
    // private function getUserId()
    // {
    //     $header = $this->request->getHeaderLine('Authorization');
    //     if (preg_match('/Bearer\s(\S+)/', $header, $matches)) {
    //         $token = $matches[1];
    //         try {
    //             $key = getenv('JWT_SECRET');
    //             $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($key, 'HS256'));
    //             return $decoded->uid;
    //         } catch (\Exception $e) {
    //             return null;
    //         }
    //     }
    //     return null;
    // }

    /**
     * GET /api/v1/forums?page=1
     * Fetches a paginated list of podcasts that have active forum threads.
     */
    public function index()
    {
        $podcastModel = new PodcastModel();

        // Fetch podcasts and calculate total interactions (Threads + Replies) on the fly!
        $rawPodcasts = $podcastModel
            ->select('podcasts.*, categories.name as category_name, themes.name as theme_text')
            // Count Threads
            ->select('(SELECT COUNT(*) FROM forum_threads WHERE forum_threads.podcast_id = podcasts.id AND forum_threads.deleted_at IS NULL) as thread_count')
            // Count all replies across all threads for this podcast
            ->select('(SELECT COUNT(*) FROM forum_replies WHERE thread_id IN (SELECT id FROM forum_threads WHERE podcast_id = podcasts.id AND deleted_at IS NULL) AND deleted_at IS NULL) as reply_count')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->join('themes', 'themes.id = podcasts.theme_id', 'left')
            ->where('podcasts.status', 'published')
            ->having('thread_count >', 0) // Only load active forums!
            ->orderBy('podcasts.created_at', 'DESC')
            ->paginate(15);

        $pager = $podcastModel->pager;

        // Use our base formatting engine
        $formattedPodcasts = $this->formatPodcastsWithAuthors($rawPodcasts);

        // Inject the combined total comments count into the payload
        foreach ($formattedPodcasts as $index => $fp) {
            $formattedPodcasts[$index]['total_comments'] = (int)$rawPodcasts[$index]['thread_count'] + (int)$rawPodcasts[$index]['reply_count'];
        }

        $payload = [
            'podcasts'   => $formattedPodcasts,
            'pagination' => [
                'current_page' => $pager->getCurrentPage(),
                'total_pages'  => $pager->getPageCount(),
                'total_items'  => $pager->getTotal(),
                'per_page'     => $pager->getPerPage()
            ]
        ];

        return $this->sendSuccess($payload, 'Forum list loaded successfully');
    }

    /**
     * GET /api/v1/forums/{slug}/comments
     * Loads the podcast details AND the structured Thread -> Reply data.
     */
    public function comments_old($slug)
    {
        $podcastModel = new PodcastModel();
        $db = \Config\Database::connect();

        // 1. Fetch the Podcast Data
        $rawPodcast = $podcastModel
            ->select('podcasts.*, categories.name as category_name, themes.name as theme_text')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->join('themes', 'themes.id = podcasts.theme_id', 'left')
            ->where('podcasts.slug', $slug)
            ->where('podcasts.status', 'published')
            ->first();

        if (!$rawPodcast) return $this->sendError('Podcast not found', 404);

        $formattedPodcast = $this->formatPodcastsWithAuthors([$rawPodcast])[0];

        // 2. Fetch all Threads for this podcast
        $threads = $db->table('forum_threads')
            ->select('forum_threads.id as thread_id, forum_threads.title, forum_threads.status, forum_threads.created_at, users.id as user_id, users.first_name, users.last_name, users.profile_image_url')
            ->join('users', 'users.id = forum_threads.user_id')
            ->where('forum_threads.podcast_id', $rawPodcast['id'])
            ->where('forum_threads.deleted_at', null)
            ->orderBy('forum_threads.created_at', 'DESC')
            ->get()->getResultArray();

        // 3. Fetch all Replies for these Threads
        $threadIds = array_column($threads, 'thread_id');
        $allReplies = [];
        if (!empty($threadIds)) {
            $allReplies = $db->table('forum_replies')
                ->select('forum_replies.*, users.first_name, users.last_name, users.profile_image_url, users.role')
                ->join('users', 'users.id = forum_replies.user_id')
                ->whereIn('forum_replies.thread_id', $threadIds)
                ->where('forum_replies.deleted_at', null)
                ->orderBy('forum_replies.created_at', 'ASC')
                ->get()->getResultArray();
        }

        // 4. Organize Replies into their Threads (Parent/Child structure)
        $formattedThreads = [];
        foreach ($threads as $t) {
            $threadData = [
                'thread_id' => (int)$t['thread_id'],
                'title'     => $t['title'],
                'status'    => $t['status'],
                'created_at'=> $t['created_at'],
                'user'      => [
                    'id'     => (int)$t['user_id'],
                    'name'   => trim($t['first_name'] . ' ' . $t['last_name']),
                    'avatar' => $t['profile_image_url']
                ],
                'replies'   => []
            ];

            // Filter replies belonging to this thread
            $threadReplies = array_filter($allReplies, fn($r) => $r['thread_id'] == $t['thread_id']);
            
            // Build Parent/Child nesting
            $replyIndex = [];
            $nested = [];
            foreach ($threadReplies as $r) {
                $formattedReply = [
                    'reply_id'    => (int)$r['id'],
                    'message'     => $r['message'],
                    'is_official' => in_array($r['role'], ['superadmin', 'author']),
                    'created_at'  => $r['created_at'],
                    'user'        => [
                        'name'   => trim($r['first_name'] . ' ' . $r['last_name']),
                        'avatar' => $r['profile_image_url']
                    ],
                    'children'    => []
                ];

                if ($r['parent_reply_id']) {
                    $nested[$r['parent_reply_id']][] = $formattedReply;
                } else {
                    $replyIndex[$r['id']] = $formattedReply;
                }
            }

            // Attach children
            foreach ($nested as $parentId => $childArray) {
                if (isset($replyIndex[$parentId])) {
                    $replyIndex[$parentId]['children'] = $childArray;
                }
            }

            $threadData['replies'] = array_values($replyIndex);
            $formattedThreads[] = $threadData;
        }

        $payload = [
            'podcast' => $formattedPodcast,
            'threads' => $formattedThreads
        ];

        return $this->sendSuccess($payload, 'Forum data loaded successfully');
    }
    /**
     * GET /api/v1/forums/{slug}/comments?page=1
     * Fetches ONLY the Top-Level Parent Comments (Threads), paginated.
     */
    public function comments($slug)
    {
        $podcastModel = new PodcastModel();
        $db = \Config\Database::connect();

        // 1. Fetch Podcast
        $podcast = $podcastModel->where('slug', $slug)->where('status', 'published')->first();
        if (!$podcast) return $this->sendError('Podcast not found', 404);

        $page = (int) ($this->request->getVar('page') ?? 1);
        $perPage = 15;

        // 2. Fetch the Threads with their Initial Message and Reply Count
        // We use a powerful subquery to grab the FIRST reply (which acts as the main comment body)
        $builder = $db->table('forum_threads')
            ->select('
                forum_threads.id as thread_id, 
                forum_threads.created_at, 
                users.id as user_id, 
                users.first_name, 
                users.last_name, 
                users.profile_image_url,
                users.role,
                (SELECT message FROM forum_replies WHERE thread_id = forum_threads.id ORDER BY id ASC LIMIT 1) as comment_body,
                (SELECT COUNT(*) FROM forum_replies WHERE thread_id = forum_threads.id) - 1 as reply_count
            ')
            ->join('users', 'users.id = forum_threads.user_id')
            ->where('forum_threads.podcast_id', $podcast['id'])
            ->where('forum_threads.deleted_at', null)
            ->orderBy('forum_threads.created_at', 'DESC');

        // Manual Pagination
        $totalItems = $builder->countAllResults(false); // Count without resetting query
        $threads = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        
        $totalPages = ceil($totalItems / $perPage);
        if ($page > $totalPages) $threads = [];

        // 3. Format exactly for the Flutter App
        $formattedThreads = [];
        foreach ($threads as $t) {
            $formattedThreads[] = [
                'thread_id'    => (int)$t['thread_id'],
                'message'      => $t['comment_body'],
                'reply_count'  => (int)$t['reply_count'],
                'created_at'   => $t['created_at'],
                'is_official'  => in_array($t['role'], ['superadmin', 'author']),
                'user'         => [
                    'id'     => (int)$t['user_id'],
                    'name'   => trim($t['first_name'] . ' ' . $t['last_name']),
                    'avatar' => $t['profile_image_url']
                ]
            ];
        }

        $payload = [
            'threads'    => $formattedThreads,
            'pagination' => [
                'current_page' => $page,
                'total_pages'  => $totalPages,
                'total_items'  => $totalItems,
                'per_page'     => $perPage
            ]
        ];

        return $this->sendSuccess($payload, 'Parent comments loaded successfully');
    }

    /**
     * GET /api/v1/forums/threads/{thread_id}/replies?page=1
     * Fetches the nested replies for a specific thread, paginated!
     */
    public function threadReplies($threadId)
    {
        $db = \Config\Database::connect();
        $page = (int) ($this->request->getVar('page') ?? 1);
        $perPage = 10; // Load 10 replies at a time when they click "View Replies"

        // 1. Find the ID of the FIRST reply (because that is the Parent Comment Body, we don't want to load it again)
        $firstReply = $db->table('forum_replies')
            ->select('id')
            ->where('thread_id', $threadId)
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->get()->getRowArray();

        if (!$firstReply) return $this->sendError('Thread not found', 404);

        // 2. Fetch the actual replies, joining the user AND the parent user (if they tagged someone)
        $builder = $db->table('forum_replies')
            ->select('
                forum_replies.id as reply_id, 
                forum_replies.message, 
                forum_replies.created_at, 
                users.id as user_id,
                users.first_name, 
                users.last_name, 
                users.profile_image_url, 
                users.role,
                parent_user.first_name as replying_to_first,
                parent_user.last_name as replying_to_last
            ')
            ->join('users', 'users.id = forum_replies.user_id')
            // This join finds who they are replying to!
            ->join('forum_replies as parent_reply', 'parent_reply.id = forum_replies.parent_reply_id', 'left')
            ->join('users as parent_user', 'parent_user.id = parent_reply.user_id', 'left')
            ->where('forum_replies.thread_id', $threadId)
            ->where('forum_replies.id !=', $firstReply['id']) // Skip the parent body!
            ->where('forum_replies.deleted_at', null)
            ->orderBy('forum_replies.created_at', 'ASC'); // Oldest replies first (like YouTube)

        $totalItems = $builder->countAllResults(false);
        $replies = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();
        
        $totalPages = ceil($totalItems / $perPage);
        
        $formattedReplies = [];
        foreach ($replies as $r) {
            
            // Build the "@User" tag if they replied to a specific person
            $replyingTo = null;
            if (!empty($r['replying_to_first'])) {
                $replyingTo = trim($r['replying_to_first'] . ' ' . $r['replying_to_last']);
            }

            $formattedReplies[] = [
                'reply_id'    => (int)$r['reply_id'],
                'message'     => $r['message'],
                'created_at'  => $r['created_at'],
                'replying_to' => $replyingTo, // Flutter will use this to show "@John" in blue!
                'is_official' => in_array($r['role'], ['superadmin', 'author']),
                'user'        => [
                    'id'     => (int)$r['user_id'],
                    'name'   => trim($r['first_name'] . ' ' . $r['last_name']),
                    'avatar' => $r['profile_image_url']
                ]
            ];
        }

        $payload = [
            'replies'    => $formattedReplies,
            'pagination' => [
                'current_page' => $page,
                'total_pages'  => $totalPages,
                'total_items'  => $totalItems,
                'per_page'     => $perPage
            ]
        ];

        return $this->sendSuccess($payload, 'Replies loaded successfully');
    }

    /**
     * POST /api/v1/forums/{slug}/comments
     * Protected Route: Translates Mobile App comments into the Admin Thread/Reply system.
     */
    public function createComment($slug)
    {
        $userId = $this->getUserId();
        if (!$userId) return $this->sendError('Unauthorized. Please log in.', 401);

        $message = $this->request->getPost('message');
        if (empty($message) || strlen(trim($message)) < 2) {
            return $this->sendError('Validation failed', 400, ['message' => 'Comment is too short.']);
        }

        $db = \Config\Database::connect();
        
        // Find the podcast ID
        $podcast = $db->table('podcasts')->where('slug', $slug)->get()->getRowArray();
        if (!$podcast) return $this->sendError('Podcast not found', 404);

        $threadId = $this->request->getPost('thread_id');
        $parentReplyId = $this->request->getPost('parent_reply_id');

        $threadModel = new ForumThreadModel();
        $replyModel = new ForumReplyModel();

        $db->transStart();

        if (empty($threadId)) {
            // SCENARIO 1: New Top-Level Comment. 
            // We create a new Thread, and immediately add the comment as the first Reply.
            
            // Generate a short title from the message (e.g. first 50 chars)
            $title = strlen($message) > 50 ? substr($message, 0, 47) . '...' : $message;

            $threadId = $threadModel->insert([
                'podcast_id' => $podcast['id'],
                'user_id'    => $userId,
                'title'      => trim($title),
                'status'     => 'open'
            ]);

            $replyModel->insert([
                'thread_id'       => $threadId,
                'parent_reply_id' => null,
                'user_id'         => $userId,
                'message'         => trim($message)
            ]);

        } else {
            // SCENARIO 2: Replying to an existing Thread (or a specific user's reply)
            $replyModel->insert([
                'thread_id'       => $threadId,
                'parent_reply_id' => $parentReplyId ?: null,
                'user_id'         => $userId,
                'message'         => trim($message)
            ]);

            // Update thread timestamp so it bumps to the top of the Admin Inbox!
            $threadModel->update($threadId, ['status' => 'open']);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->sendError('Failed to post comment', 500);
        }

        return $this->sendSuccess(null, 'Comment posted successfully', 201);
    }
}