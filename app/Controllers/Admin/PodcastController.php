<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PodcastModel;
use App\Models\PodcastAuthorModel;
use App\Models\CategoryModel;
use App\Models\ThemeModel;
use App\Models\UserModel;

class PodcastController extends BaseController
{
    protected $podcastModel;

    public function __construct()
    {
        $this->podcastModel = new PodcastModel();
        helper(['form', 'url', 'text']);
    }

    // Displays the list of podcasts
    public function index()
    {
        $userId = session()->get('user_id');
        $role = session()->get('role');

        $builder = $this->podcastModel
            ->select('podcasts.*, categories.name as category_name')
            ->join('categories', 'categories.id = podcasts.category_id', 'left');

        // Apply Author restriction via the pivot table
        if ($role === 'author') {
            $builder->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id')
                    ->where('podcast_authors.author_id', $userId)
                    ->groupBy('podcasts.id');
        }

        $podcasts = $builder->orderBy('podcasts.created_at', 'DESC')->findAll();

        $data = [
            'title'    => 'Manage Podcasts',
            'podcasts' => $podcasts
        ];
        return view('admin/podcasts/index', $data);
    }
    // Displays the list of podcasts
    public function index_old()
    {
        // We use a JOIN to grab the Category name elegantly
        $podcasts = $this->podcastModel
            ->select('podcasts.*, categories.name as category_name')
            ->join('categories', 'categories.id = podcasts.category_id', 'left')
            ->orderBy('podcasts.created_at', 'DESC')
            ->findAll();

        $data = [
            'title'    => 'Manage Podcasts',
            'podcasts' => $podcasts
        ];
        return view('admin/podcasts/index', $data);
    }

    // Opens the Wizard
    public function wizard_old()
    {
        // We need this data for the dropdown menus!
        $categoryModel = new CategoryModel();
        $themeModel = new ThemeModel();
        $userModel = new UserModel();

        $data = [
            'title'      => 'Upload New Podcast',
            'categories' => $categoryModel->findAll(),
            'themes'     => $themeModel->findAll(),
            // Only fetch users who are Authors or Superadmins
            'authors'    => $userModel->whereIn('role', ['author', 'superadmin'])->findAll(),
        ];

        return view('admin/podcasts/wizard', $data);
    }

private function getWizardData()
    {
        return [
            'categories' => (new CategoryModel())->findAll(),
            'themes'     => (new ThemeModel())->findAll(),
            'authors'    => (new UserModel())->whereIn('role', ['author', 'superadmin'])->findAll(),
        ];
    }
    public function wizard()
    {
        $data = $this->getWizardData();
        $data['title'] = 'Upload New Podcast';
        return view('admin/podcasts/wizard', $data);
    }
    // Processes the Final Submission
    public function store()
    {
        $db = \Config\Database::connect();
        $db->transStart(); // Start Database Transaction for safety

        $title = $this->request->getPost('title');
        
        // 1. Save Core Podcast Data
        $podcastData = [
            'title'           => $title,
            'slug'            => strtolower(url_title($title)) . '-' . time(), // Ensure uniqueness
            'description'     => $this->request->getPost('description'),
            'category_id'     => $this->request->getPost('category_id'),
            'theme_id'        => $this->request->getPost('theme_id'),
            // Assuming direct URL input for Cloudflare for now
            'media_high_url'  => $this->request->getPost('media_high_url'), 
            'media_low_url'   => $this->request->getPost('media_low_url'),
            'status'          => $this->request->getPost('status'),
            'published_at'    => $this->request->getPost('status') === 'published' ? date('Y-m-d H:i:s') : null,
            'created_by'      => session()->get('user_id'),
        ];

        // Handle Cover Image Upload
        $file = $this->request->getFile('cover_image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/covers', $newName);
            $podcastData['cover_image_url'] = 'uploads/covers/' . $newName;
        }

        $podcastId = $this->podcastModel->insert($podcastData);

        // 2. Save Authorship (Primary)
        $authorModel = new PodcastAuthorModel();
        $primaryAuthorId = $this->request->getPost('primary_author_id');
        
        $authorModel->insert([
            'podcast_id' => $podcastId,
            'author_id'  => $primaryAuthorId,
            'is_primary' => 1,
            'can_edit'   => 1
        ]);

        // 3. Save Co-Authors (If any)
        $coAuthors = $this->request->getPost('co_authors'); // Array of IDs
        if (!empty($coAuthors)) {
            foreach ($coAuthors as $coAuthorId) {
                if ($coAuthorId != $primaryAuthorId) { // Prevent duplicate primary
                    $authorModel->insert([
                        'podcast_id' => $podcastId,
                        'author_id'  => $coAuthorId,
                        'is_primary' => 0,
                        'can_edit'   => $this->request->getPost('co_authors_can_edit') ? 1 : 0
                    ]);
                }
            }
        }

        $db->transComplete(); // Commit Database Transaction

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'An error occurred while saving.');
        }

        return redirect()->to('admin/podcasts')->with('success', 'Podcast published successfully!');
    }

    public function edit($id)
    {
        $podcast = $this->podcastModel->find($id);
        if (!$podcast) return redirect()->to('admin/podcasts')->with('error', 'Podcast not found.');

        // Get authorship data to pre-fill the form
        $authorModel = new PodcastAuthorModel();
        $primaryAuthor = $authorModel->where('podcast_id', $id)->where('is_primary', 1)->first();
        
        $data = $this->getWizardData();
        $data['title'] = 'Edit Podcast';
        $data['podcast'] = $podcast;
        $data['primary_author_id'] = $primaryAuthor ? $primaryAuthor['author_id'] : '';
        
        return view('admin/podcasts/wizard', $data);
    }

    public function save($id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        $title = $this->request->getPost('title');
        
        $podcastData = [
            'title'           => $title,
            'description'     => $this->request->getPost('description'),
            'category_id'     => $this->request->getPost('category_id'),
            'theme_id'        => $this->request->getPost('theme_id') ?: null,
            'media_high_url'  => $this->request->getPost('media_high_url'), 
            'media_low_url'   => $this->request->getPost('media_low_url'),
            'status'          => $this->request->getPost('status'),
        ];

        // Only generate a new slug and set creator if it's a NEW upload
        if (!$id) {
            $podcastData['slug'] = strtolower(url_title($title)) . '-' . time();
            $podcastData['created_by'] = session()->get('user_id');
            $podcastData['published_at'] = $this->request->getPost('status') === 'published' ? date('Y-m-d H:i:s') : null;
        }

        // Handle Cover Image Upload
        $file = $this->request->getFile('cover_image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/covers', $newName);
            $podcastData['cover_image_url'] = 'uploads/covers/' . $newName;
        }

        // Save or Update Podcast
        if ($id) {
            $this->podcastModel->update($id, $podcastData);
            $podcastId = $id;
        } else {
            $podcastId = $this->podcastModel->insert($podcastData);
        }

        // Handle Authorship (Delete old authors and re-insert to keep it clean)
        $authorModel = new PodcastAuthorModel();
        if ($id) {
            $authorModel->where('podcast_id', $id)->delete(); 
        }

        $primaryAuthorId = $this->request->getPost('primary_author_id');
        $authorModel->insert(['podcast_id' => $podcastId, 'author_id' => $primaryAuthorId, 'is_primary' => 1, 'can_edit' => 1]);

        $coAuthors = $this->request->getPost('co_authors') ?? [];
        foreach ($coAuthors as $coAuthorId) {
            if ($coAuthorId != $primaryAuthorId) {
                $authorModel->insert([
                    'podcast_id' => $podcastId, 
                    'author_id' => $coAuthorId, 
                    'is_primary' => 0, 
                    'can_edit' => $this->request->getPost('co_authors_can_edit') ? 1 : 0
                ]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'An error occurred while saving.');
        }

        return redirect()->to('admin/podcasts')->with('success', $id ? 'Podcast updated successfully!' : 'Podcast published successfully!');
    }

    // Safely Soft-Deletes the Podcast
    public function delete($id)
    {
        $this->podcastModel->delete($id);
        return redirect()->to('admin/podcasts')->with('success', 'Podcast deleted successfully.');
    }
}