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
public function index()
    {
        $userId = session()->get('user_id');
        $role   = session()->get('role');
        $search = $this->request->getGet('search');

        $builder = $this->podcastModel
            ->select('podcasts.*, categories.name as category_name')
            ->join('categories', 'categories.id = podcasts.category_id', 'left');

        // Apply Author restriction via the pivot table
        if ($role === 'author') {
            $builder->join('podcast_authors', 'podcast_authors.podcast_id = podcasts.id')
                    ->where('podcast_authors.author_id', $userId)
                    ->groupBy('podcasts.id');
        }

        // Apply Search Filter Safely
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('podcasts.title', $search)
                    ->orLike('podcasts.description', $search)
                    ->groupEnd();
        }

        // Use paginate(15) instead of findAll()
        $podcasts = $builder->orderBy('podcasts.created_at', 'DESC')->paginate(15);

        $data = [
            'title'    => 'Manage Podcasts',
            'podcasts' => $podcasts,
            'pager'    => $this->podcastModel->pager, // Pass the pager engine to the view
            'search'   => $search                     // Pass the search string back to the view
        ];
        
        return view('admin/podcasts/index', $data);
    }
    // Displays the list of podcasts
    public function index_old_before_pagination()
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
    public function store_old()
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

    public function store()
    {
        $db = \Config\Database::connect();
        $db->transStart(); // Start Database Transaction for safety

        $title = $this->request->getPost('title');
        
        // 1. Initialize Podcast Data
        $podcastData = [
            'title'           => $title,
            'slug'            => strtolower(url_title($title)) . '-' . time(), // Ensure uniqueness
            'description'     => $this->request->getPost('description'),
            'category_id'     => $this->request->getPost('category_id'),
            'theme_id'        => $this->request->getPost('theme_id'),
            // Assuming direct URL input for audio for now
            'media_high_url'  => $this->request->getPost('media_high_url'), 
            'media_low_url'   => $this->request->getPost('media_low_url'),
            'status'          => $this->request->getPost('status'),
            'published_at'    => $this->request->getPost('status') === 'published' ? date('Y-m-d H:i:s') : null,
            'created_by'      => session()->get('user_id'),
        ];

        // --------------------------------------------------------------------
        // CLOUDFLARE R2: Handle Cover Image Upload
        // --------------------------------------------------------------------
        $file = $this->request->getFile('cover_image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            
            // Call our custom library
            $cloudflare = new \App\Libraries\CloudflareStorage();
            
            // Upload to the 'podcasts/covers' folder in your R2 bucket
            $coverUrl = $cloudflare->upload($file, 'podcasts/covers');
            
            if ($coverUrl) {
                // Success! Save the public Cloudflare URL to the database
                $podcastData['cover_image_url'] = $coverUrl;
            } else {
                // If the cloud upload fails, abort the database save!
                $db->transRollback();
                return redirect()->back()->withInput()->with('error', 'Failed to upload cover image to the cloud. Please try again.');
            }
        }

        // Insert the main podcast record
        $podcastId = $this->podcastModel->insert($podcastData);

        // 2. Save Authorship (Primary)
        $authorModel = new \App\Models\PodcastAuthorModel(); // Make sure this is imported at the top, or fully qualified here
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
            return redirect()->back()->withInput()->with('error', 'An error occurred while saving the database records.');
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

    public function save_old($id = null)
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

    public function save_old_2($id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart(); // Start Database Transaction for safety

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
        $oldPodcast = null;
        // if (!$id) {
        //     $podcastData['slug'] = strtolower(url_title($title)) . '-' . time();
        //     $podcastData['created_by'] = session()->get('user_id');
        //     $podcastData['published_at'] = $this->request->getPost('status') === 'published' ? date('Y-m-d H:i:s') : null;
        // }

        if ($id) {
            $oldPodcast = $this->podcastModel->find($id);
        } else {
            // It's a new podcast
            $podcastData['slug'] = strtolower(url_title($title)) . '-' . time();
            $podcastData['created_by'] = session()->get('user_id');
            $podcastData['published_at'] = $this->request->getPost('status') === 'published' ? date('Y-m-d H:i:s') : null;
        }

        // --------------------------------------------------------------------
        // CLOUDFLARE R2: Handle Cover Image Upload
        // --------------------------------------------------------------------
        $file = $this->request->getFile('cover_image');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            
            // Initialize our Cloudflare Service
            $cloudflare = new \App\Libraries\CloudflareStorage();
            
            // Upload to the 'podcasts/covers' folder
            $coverUrl = $cloudflare->upload($file, 'podcasts/covers');
            
            if ($coverUrl) {
                // Success! Assign the public Cloudflare URL
                $podcastData['cover_image_url'] = $coverUrl;
                if ($oldPodcast && !empty($oldPodcast['cover_image_url'])) {
                    $cloudflare->delete($oldPodcast['cover_image_url']);
                }
            } else {
                // Abort the entire database transaction if cloud upload fails
                $db->transRollback();
                return redirect()->back()->withInput()->with('error', 'Failed to upload cover image to the cloud. Please try again.');
            }
        }

        // Save or Update Podcast Main Record
        if ($id) {
            $this->podcastModel->update($id, $podcastData);
            $podcastId = $id;
        } else {
            $podcastId = $this->podcastModel->insert($podcastData);
        }

        // Handle Authorship (Delete old authors and re-insert to keep it clean)
        $authorModel = new \App\Models\PodcastAuthorModel();
        if ($id) {
            $authorModel->where('podcast_id', $id)->delete(); 
        }

        $primaryAuthorId = $this->request->getPost('primary_author_id');
        $authorModel->insert([
            'podcast_id' => $podcastId, 
            'author_id'  => $primaryAuthorId, 
            'is_primary' => 1, 
            'can_edit'   => 1
        ]);

        $coAuthors = $this->request->getPost('co_authors') ?? [];
        foreach ($coAuthors as $coAuthorId) {
            if ($coAuthorId != $primaryAuthorId) {
                $authorModel->insert([
                    'podcast_id' => $podcastId, 
                    'author_id'  => $coAuthorId, 
                    'is_primary' => 0, 
                    'can_edit'   => $this->request->getPost('co_authors_can_edit') ? 1 : 0
                ]);
            }
        }

        $db->transComplete(); // Commit Database Transaction

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

    public function save($id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart(); 

        $title = $this->request->getPost('title');
        
        $podcastData = [
            'title'       => $title,
            'description' => $this->request->getPost('description'),
            'category_id' => $this->request->getPost('category_id'),
            'theme_id'    => $this->request->getPost('theme_id') ?: null,
            'status'      => $this->request->getPost('status'),
        ];

        $oldPodcast = null;
        if ($id) {
            $oldPodcast = $this->podcastModel->find($id);
        } else {
            $podcastData['slug'] = strtolower(url_title($title)) . '-' . time();
            $podcastData['created_by'] = session()->get('user_id');
            $podcastData['published_at'] = $this->request->getPost('status') === 'published' ? date('Y-m-d H:i:s') : null;
        }

        $cloudflare = new \App\Libraries\CloudflareStorage();

        // --------------------------------------------------------------------
        // 1. CLOUDFLARE R2: Handle Cover Image (For Both New & Updates)
        // --------------------------------------------------------------------
        $coverFile = $this->request->getFile('cover_image');
        if ($coverFile && $coverFile->isValid() && !$coverFile->hasMoved()) {
            $coverUrl = $cloudflare->upload($coverFile, 'podcasts/covers');
            if ($coverUrl) {
                $podcastData['cover_image_url'] = $coverUrl;
                if ($oldPodcast && !empty($oldPodcast['cover_image_url'])) {
                    $cloudflare->delete($oldPodcast['cover_image_url']);
                }
            } else {
                $db->transRollback();
                return $this->respondWithError('Failed to upload cover image to Cloudflare.');
            }
        }

        // --------------------------------------------------------------------
        // 2. CLOUDFLARE R2: Handle MP3 Uploads (ONLY FOR NEW PODCASTS)
        // --------------------------------------------------------------------
        if (!$id) {
            // High Quality MP3 (Required)
            $highFile = $this->request->getFile('media_high');
            if ($highFile && $highFile->isValid() && !$highFile->hasMoved()) {
                $highUrl = $cloudflare->upload($highFile, 'podcasts/audio/high');
                if ($highUrl) {
                    $podcastData['media_high_url'] = $highUrl;
                } else {
                    $db->transRollback();
                    return $this->respondWithError('Failed to upload High Quality MP3 to Cloudflare.');
                }
            } else {
                $db->transRollback();
                return $this->respondWithError('High Quality MP3 file is required for new uploads.');
            }

            // Low Quality AAC/MP3 (Optional)
            $lowFile = $this->request->getFile('media_low');
            if ($lowFile && $lowFile->isValid() && !$lowFile->hasMoved()) {
                $lowUrl = $cloudflare->upload($lowFile, 'podcasts/audio/low');
                if ($lowUrl) {
                    $podcastData['media_low_url'] = $lowUrl;
                }
            }
        }

        // --------------------------------------------------------------------
        // 3. Database Updates
        // --------------------------------------------------------------------
        if ($id) {
            $this->podcastModel->update($id, $podcastData);
            $podcastId = $id;
        } else {
            $podcastId = $this->podcastModel->insert($podcastData);
        }

        $authorModel = new \App\Models\PodcastAuthorModel();
        if ($id) {
            $authorModel->where('podcast_id', $id)->delete(); 
        }

        $primaryAuthorId = $this->request->getPost('primary_author_id');
        $authorModel->insert(['podcast_id' => $podcastId, 'author_id' => $primaryAuthorId, 'is_primary' => 1, 'can_edit' => 1]);

        $coAuthors = $this->request->getPost('co_authors') ?? [];
        foreach ($coAuthors as $coAuthorId) {
            if ($coAuthorId != $primaryAuthorId) {
                $authorModel->insert(['podcast_id' => $podcastId, 'author_id' => $coAuthorId, 'is_primary' => 0, 'can_edit' => $this->request->getPost('co_authors_can_edit') ? 1 : 0]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->respondWithError('A database error occurred while saving.');
        }

        // Set session success and tell Alpine to redirect!
        session()->setFlashdata('success', $id ? 'Podcast updated successfully!' : 'Podcast published successfully!');
        
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'redirect' => site_url('admin/podcasts')]);
        }
        return redirect()->to('admin/podcasts');
    }

    // Helper function to handle AJAX errors gracefully
    private function respondWithError($message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => $message]);
        }
        return redirect()->back()->withInput()->with('error', $message);
    }

    // Loads the Media Editor View
    public function media($id)
    {
        $podcast = $this->podcastModel->find($id);
        if (!$podcast) {
            return redirect()->to('admin/podcasts')->with('error', 'Podcast not found.');
        }

        $data = [
            'title'   => 'Manage Audio: ' . $podcast['title'],
            'podcast' => $podcast
        ];

        return view('admin/podcasts/media', $data);
    }

    // Handles the AJAX Audio Upload & Replacement
    public function updateMedia($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('admin/podcasts');
        }

        $podcast = $this->podcastModel->find($id);
        if (!$podcast) {
            return $this->response->setJSON(['success' => false, 'message' => 'Podcast not found.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $cloudflare = new \App\Libraries\CloudflareStorage();
        $updateData = [];

        // 1. Handle NEW High Quality Audio
        $highFile = $this->request->getFile('media_high');
        if ($highFile && $highFile->isValid() && !$highFile->hasMoved()) {
            $highUrl = $cloudflare->upload($highFile, 'podcasts/audio/high');
            
            if ($highUrl) {
                $updateData['media_high_url'] = $highUrl;
                // Delete the OLD High Quality file from Cloudflare!
                if (!empty($podcast['media_high_url'])) {
                    $cloudflare->delete($podcast['media_high_url']);
                }
            } else {
                $db->transRollback();
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to upload High Quality MP3 to cloud.']);
            }
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'High Quality MP3 file is required to update media.']);
        }

        // 2. Handle NEW Low Quality Audio (Optional)
        $lowFile = $this->request->getFile('media_low');
        if ($lowFile && $lowFile->isValid() && !$lowFile->hasMoved()) {
            $lowUrl = $cloudflare->upload($lowFile, 'podcasts/audio/low');
            
            if ($lowUrl) {
                $updateData['media_low_url'] = $lowUrl;
                // Delete the OLD Low Quality file from Cloudflare!
                if (!empty($podcast['media_low_url'])) {
                    $cloudflare->delete($podcast['media_low_url']);
                }
            }
        }

        // 3. Update Database
        $this->podcastModel->update($id, $updateData);
        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['success' => false, 'message' => 'Database error occurred.']);
        }

        session()->setFlashdata('success', 'Audio files successfully updated!');
        return $this->response->setJSON(['success' => true, 'redirect' => site_url('admin/podcasts')]);
    }
}