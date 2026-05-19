<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {

        $db = \Config\Database::connect();
        $userId = session()->get('user_id');
        $role = session()->get('role');
         if ($role === 'reviewer') {
                return redirect()->to('admin/reviews')->with('success', 'Welcome back, ');

         }

        // 1. Build the base query for podcasts
        $builder = $db->table('podcasts')
                      ->select('podcasts.*, categories.name as category_name')
                      ->join('categories', 'categories.id = podcasts.category_id', 'left')
                      ->where('podcasts.deleted_at', null); // Ignore soft-deleted

        // 2. If it's an author, restrict the query to ONLY their assigned podcasts
        if ($role === 'author') {
            $builder->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id')
                    ->where('podcast_authors.author_id', $userId)
                    ->groupBy('podcasts.id');
        }

        // 3. Clone the builder to get total counts before we apply the LIMIT for the table
        $countBuilder = clone $builder;
        $totalPodcasts = $countBuilder->countAllResults(false); // false prevents resetting the query

        // 4. Get the 5 most recent uploads for the data table
        $recentPodcasts = $builder->orderBy('podcasts.created_at', 'DESC')
                                  ->limit(5)
                                  ->get()
                                  ->getResultArray();

        $data = [
            'title'          => 'Dashboard Overview',
            'totalPodcasts'  => $totalPodcasts,
            'recentPodcasts' => $recentPodcasts
        ];

        return view('admin/dashboard/index', $data);
    }
}