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
                'id'       => (int) $author['id'],
                // Combine first and last name
                'name'     => trim(($author['first_name'] ?? '') . ' ' . ($author['last_name'] ?? '')),
                'bio'      => $author['bio'] ?? '',
                // Fallback to avatar if profile image is null
                'imageUrl' => $author['profile_image_url'] ?? $author['avatar_url'] ?? null, 
                'churchId' => isset($author['church_id']) ? (int) $author['church_id'] : null,
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