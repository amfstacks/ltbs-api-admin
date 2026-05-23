<?php

namespace App\Controllers\Api;

use App\Models\UserModel;

class AuthorController extends BaseApiController
{
    /**
     * GET /api/v1/authors?limit=6&page=1
     */
    public function index()
    {
        $limit = (int) $this->request->getVar('limit') ?: 20;
        $page = (int) $this->request->getVar('page') ?: 1;
        $offset = ($page - 1) * $limit;

        $userModel = new UserModel();

        // 1. Set the conditions for "Authors"
        $userModel->where('role', 'author')
                  ->where('status', 'active');

        // 2. Clone the query to get the total count before limiting
        $total = $userModel->countAllResults(false);

        // 3. Fetch the actual paginated data
        $authors = $userModel->orderBy('first_name', 'ASC')
                             ->orderBy('last_name', 'ASC')
                             ->findAll($limit, $offset);

        // 4. Format securely for Flutter (Don't send password hashes!)
       $formattedAuthors = array_map(function($author) {
    return [
        'user' => [
            'id'                => $author['id'],
            'title'                => $author['title'],
            'first_name'        => $author['first_name'],
            'last_name'         => $author['last_name'] ?? '',
            'bio'               => $author['bio'],
            'profile_image_url' => media_url($author['profile_image_url']),
            'churchId'          => $author['churchId'] ?? null,
        ],
        'stats' => [
            'total_listens'       => $author['total_listens'] ?? 0,
            'forum_contributions' => $author['forum_contributions'] ?? 0,
        ]
    ];
}, $authors);

        return $this->sendSuccess([
            'authors' => $formattedAuthors,
            'pagination' => [
                'current_page' => $page,
                'limit'        => $limit,
                'total_items'  => $total,
                'total_pages'  => ceil($total / $limit),
                'has_more'     => ($offset + $limit) < $total
            ]
        ], 'Authors retrieved successfully');
    }
}