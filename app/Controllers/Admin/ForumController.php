<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ForumThreadModel;
use App\Models\ForumReplyModel;

class ForumController extends BaseController
{
    // 1. The Inbox (List of Threads)
    // public function index()
    // {
    //     $db = \Config\Database::connect();
    //     $userId = session()->get('user_id');
    //     $role = session()->get('role');

    //     $builder = $db->table('forum_threads')
    //         ->select('forum_threads.*, podcasts.title as podcast_title, users.first_name, users.last_name, 
    //                   (SELECT COUNT(*) FROM forum_replies WHERE forum_replies.thread_id = forum_threads.id) as reply_count')
    //         ->join('podcasts', 'podcasts.id = forum_threads.podcast_id')
    //         ->join('users', 'users.id = forum_threads.user_id')
    //         ->where('forum_threads.deleted_at', null);

    //     // RBAC: Authors only see threads for their own podcasts
    //     if ($role === 'author') {
    //         $builder->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id')
    //                 ->where('podcast_authors.author_id', $userId);
    //     }

    //     $data = [
    //         'title'   => 'Forum Inbox',
    //         'threads' => $builder->orderBy('forum_threads.updated_at', 'DESC')->get()->getResultArray()
    //     ];

    //     return view('admin/forum/index', $data);
    // }

    // // 2. The Chat View
    // public function view($id)
    // {
    //     // First, securely fetch the thread ensuring RBAC is respected
    //     $db = \Config\Database::connect();
    //     $userId = session()->get('user_id');
    //     $role = session()->get('role');

    //     $builder = $db->table('forum_threads')
    //         ->select('forum_threads.*, podcasts.title as podcast_title, users.first_name, users.last_name')
    //         ->join('podcasts', 'podcasts.id = forum_threads.podcast_id')
    //         ->join('users', 'users.id = forum_threads.user_id')
    //         ->where('forum_threads.id', $id);

    //     if ($role === 'author') {
    //         $builder->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id')
    //                 ->where('podcast_authors.author_id', $userId);
    //     }

    //     $thread = $builder->get()->getRowArray();

    //     if (!$thread) {
    //         return redirect()->to('admin/forum')->with('error', 'Thread not found or access denied.');
    //     }

    //     $data = ['title' => 'Thread: ' . $thread['title'], 'thread' => $thread];
    //     return view('admin/forum/view', $data);
    // }

    // 1. The Inbox (With Unread Calculations)
    public function index_old()
    {
        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $role = session()->get('role');

        $builder = $db->table('forum_threads')
            ->select("
                forum_threads.*, 
                podcasts.title as podcast_title, 
                users.first_name, users.last_name, 
                (SELECT COUNT(*) FROM forum_replies WHERE forum_replies.thread_id = forum_threads.id) as reply_count,
                
                -- The Magic Unread Calculator!
                (SELECT COUNT(*) FROM forum_replies 
                 WHERE forum_replies.thread_id = forum_threads.id 
                 AND forum_replies.created_at > IFNULL((SELECT last_read_at FROM forum_thread_reads WHERE thread_id = forum_threads.id AND user_id = {$db->escape($userId)}), '2000-01-01')
                ) as unread_count
            ")
            ->join('podcasts', 'podcasts.id = forum_threads.podcast_id')
            ->join('users', 'users.id = forum_threads.user_id')
            ->where('forum_threads.deleted_at', null);

        if ($role === 'author') {
            $builder->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id')
                    ->where('podcast_authors.author_id', $userId);
        }

        $data = [
            'title'   => 'Forum Inbox',
            'threads' => $builder->orderBy('unread_count', 'DESC')->orderBy('forum_threads.updated_at', 'DESC')->get()->getResultArray()
        ];

        return view('admin/forum/index', $data);
    }

    // 1. Level One: The Podcast Inbox
    public function index()
    {
        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $role = session()->get('role');

        // We grab the Podcasts and calculate forum metrics on the fly!
        $builder = $db->table('podcasts')
            ->select("
                podcasts.id, podcasts.title, podcasts.cover_image_url,
                categories.name as category_name,
                (SELECT COUNT(*) FROM forum_threads WHERE podcast_id = podcasts.id AND deleted_at IS NULL) as total_threads,
                
                -- The Magic Awaiting Reply Calculator!
                (
                    SELECT COUNT(*) FROM forum_threads ft
                    WHERE ft.podcast_id = podcasts.id AND ft.deleted_at IS NULL
                    AND (
                        (SELECT COUNT(*) FROM forum_replies fr WHERE fr.thread_id = ft.id) = 0
                        OR 
                        (SELECT u.role FROM forum_replies fr2 JOIN users u ON u.id = fr2.user_id WHERE fr2.thread_id = ft.id ORDER BY fr2.created_at DESC LIMIT 1) = 'app_user'
                    )
                ) as awaiting_count
            ")
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->where('podcasts.deleted_at', null);

        if ($role === 'author') {
            $builder->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id')
                    ->where('podcast_authors.author_id', $userId);
        }

        // Order by the ones that need attention most!
        $podcasts = $builder->having('total_threads >', 0)->orderBy('awaiting_count', 'DESC')->orderBy('total_threads', 'DESC')->get()->getResultArray();

        $data = ['title' => 'Forum Dashboard', 'podcasts' => $podcasts];
        return view('admin/forum/index', $data);
    }

    // 2. Level Two: The Ticketing System (Tabs)
    public function podcast($podcastId)
    {
        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $role = session()->get('role');

        // RBAC Verification - Can this user see this podcast?
        $podcastBuilder = $db->table('podcasts')->where('id', $podcastId);
        if ($role === 'author') {
            $podcastBuilder->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id')
                           ->where('podcast_authors.author_id', $userId);
        }
        $podcast = $podcastBuilder->get()->getRowArray();
        
        if (!$podcast) return redirect()->to('admin/forum')->with('error', 'Podcast not found or access denied.');

        // Fetch all threads for this podcast
        $threads = $db->table('forum_threads')
            ->select("
                forum_threads.*,
                users.first_name, users.last_name,
                (SELECT COUNT(*) FROM forum_replies WHERE thread_id = forum_threads.id) as reply_count,
                (SELECT u.role FROM forum_replies fr JOIN users u ON u.id = fr.user_id WHERE fr.thread_id = forum_threads.id ORDER BY fr.created_at DESC LIMIT 1) as last_replier_role
            ")
            ->join('users', 'users.id = forum_threads.user_id')
            ->where('forum_threads.podcast_id', $podcastId)
            ->where('forum_threads.deleted_at', null)
            ->orderBy('forum_threads.updated_at', 'DESC')
            ->get()->getResultArray();

        // Let's attach our boolean logic so Alpine.js can filter the tabs instantly
        $awaitingCount = 0;
        foreach ($threads as &$t) {
            $t['is_awaiting'] = ($t['reply_count'] == 0 || $t['last_replier_role'] === 'app_user');
            if ($t['is_awaiting']) $awaitingCount++;
        }

        $data = [
            'title'         => 'Discussions: ' . $podcast['title'],
            'podcast'       => $podcast,
            'threads'       => $threads,
            'awaitingCount' => $awaitingCount
        ];
        
        return view('admin/forum/threads', $data);
    }

    // 2. The Chat View (Marks thread as Read)
    public function view($id)
    {
        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $role = session()->get('role');

        // ... (Keep your existing RBAC $builder code to fetch $thread here) ...
        $builder = $db->table('forum_threads')
            ->select('forum_threads.*, podcasts.title as podcast_title, users.first_name, users.last_name')
            ->join('podcasts', 'podcasts.id = forum_threads.podcast_id')
            ->join('users', 'users.id = forum_threads.user_id')
            ->where('forum_threads.id', $id);

        if ($role === 'author') {
            $builder->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id')
                    ->where('podcast_authors.author_id', $userId);
        }

        $thread = $builder->get()->getRowArray();
        if (!$thread) return redirect()->to('admin/forum')->with('error', 'Thread not found or access denied.');

        // NEW: Mark this thread as READ for this specific admin
        $db->query("
            INSERT INTO forum_thread_reads (user_id, thread_id, last_read_at) 
            VALUES ({$db->escape($userId)}, {$db->escape($id)}, NOW()) 
            ON DUPLICATE KEY UPDATE last_read_at = NOW()
        ");

        $data = ['title' => 'Thread: ' . $thread['title'], 'thread' => $thread];
        return view('admin/forum/view', $data);
    }

    // 3. API Endpoint for Alpine.js "Silent Polling"
    public function fetchReplies_old($threadId)
    {
        $db = \Config\Database::connect();
        
        $replies = $db->table('forum_replies')
            ->select('forum_replies.*, users.first_name, users.last_name, users.role')
            ->join('users', 'users.id = forum_replies.user_id')
            ->where('forum_replies.thread_id', $threadId)
            ->where('forum_replies.deleted_at', null)
            ->orderBy('forum_replies.created_at', 'ASC')
            ->get()->getResultArray();

        // Format dates so JavaScript doesn't have to work hard
        foreach ($replies as &$reply) {
            $reply['time_ago'] = date('M d, g:i A', strtotime($reply['created_at']));
            // Flag if the reply is from an admin/author so we can style it differently!
            $reply['is_official'] = in_array($reply['role'], ['superadmin', 'author']);
        }

        return $this->response->setJSON($replies);
    }

    public function fetchReplies($threadId)
    {
        $db = \Config\Database::connect();
        
        $replies = $db->table('forum_replies')
            ->select('forum_replies.*, users.first_name, users.last_name, users.role, parent_users.first_name as parent_first_name')
            ->join('users', 'users.id = forum_replies.user_id')
            // Left Join to get the name of the person they are replying to
            ->join('forum_replies as parent_replies', 'parent_replies.id = forum_replies.parent_reply_id', 'left')
            ->join('users as parent_users', 'parent_users.id = parent_replies.user_id', 'left')
            ->where('forum_replies.thread_id', $threadId)
            ->where('forum_replies.deleted_at', null)
            ->orderBy('forum_replies.created_at', 'ASC')
            ->get()->getResultArray();

        foreach ($replies as &$reply) {
            $reply['time_ago'] = date('M d, g:i A', strtotime($reply['created_at']));
            $reply['is_official'] = in_array($reply['role'], ['superadmin', 'author']);
        }

        return $this->response->setJSON($replies);
    }

    // 4. Save a new reply from the Dashboard
    public function reply_old($threadId)
    {
        $replyModel = new ForumReplyModel();
        $threadModel = new ForumThreadModel();

        $message = $this->request->getPost('message');
        if (trim($message) === '') return redirect()->back()->with('error', 'Reply cannot be empty.');

        $replyModel->insert([
            'thread_id' => $threadId,
            'user_id'   => session()->get('user_id'),
            'message'   => $message
        ]);

        // "Touch" the thread to update its updated_at timestamp (pushes it to top of inbox)
        $threadModel->update($threadId, ['status' => 'open']); // Optional: mark as answered if needed

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }
    public function reply($threadId)
    {
        $replyModel = new ForumReplyModel();
        $threadModel = new ForumThreadModel();

        $message = $this->request->getPost('message');
        if (trim($message) === '') return redirect()->back()->with('error', 'Reply cannot be empty.');

        $replyModel->insert([
            'thread_id'       => $threadId,
            'parent_reply_id' => $this->request->getPost('parent_reply_id') ?: null, // Catch the parent ID!
            'user_id'         => session()->get('user_id'),
            'message'         => $message
        ]);

        $threadModel->update($threadId, ['status' => 'open']);

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }
}