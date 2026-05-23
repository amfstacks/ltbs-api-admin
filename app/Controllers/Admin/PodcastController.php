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

    public function edit_old($id)
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
    public function edit($id)
    {
        $podcast = $this->podcastModel->find($id);
        if (!$podcast) return redirect()->to('admin/podcasts')->with('error', 'Podcast not found.');

        $authorModel = new \App\Models\PodcastAuthorModel();
        
        // 1. Get Primary Author
        $primaryAuthor = $authorModel->where('podcast_id', $id)->where('is_primary', 1)->first();
        
        // 2. THE FIX: Fetch the Co-Authors!
        $coAuthors = $authorModel->where('podcast_id', $id)->where('is_primary', 0)->findAll();
        
        // Extract just the author_ids into a simple, flat array (e.g., [4, 7, 9])
        $selected_co_authors = array_column($coAuthors, 'author_id');
        
        // 3. Determine if the "Can Edit" checkbox was checked (we just check the first co-author's permission)
        $coAuthorsCanEdit = (!empty($coAuthors) && $coAuthors[0]['can_edit'] == 1) ? 1 : 0;
        
        // Temporarily attach this to the podcast array so the view's checkbox logic works
        $podcast['co_authors_can_edit'] = $coAuthorsCanEdit;
        
        $data = $this->getWizardData();
        $data['title'] = 'Edit Podcast';
        $data['podcast'] = $podcast;
        $data['primary_author_id'] = $primaryAuthor ? $primaryAuthor['author_id'] : '';
        
        // 4. Pass the array to the view!
        $data['selected_co_authors'] = $selected_co_authors; 
        
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
    // public function delete($id)
    // {
    //     $this->podcastModel->delete($id);
    //     return redirect()->to('admin/podcasts')->with('success', 'Podcast deleted successfully.');
    // }
    // Safely Archives and Hard-Deletes the Podcast
    public function delete($id)
    {
        $db = \Config\Database::connect();
        
        // 1. Fetch the existing podcast as an array
        $podcast = $this->podcastModel->find($id);
        
        if (!$podcast) {
            return redirect()->to('admin/podcasts')->with('error', 'Podcast not found.');
        }

        // Start the transaction
        $db->transStart();

        // 2. Stamp the exact deletion time
        $podcast['deleted_at'] = date('Y-m-d H:i:s');

        // 3. Move the data into the archive table
        $db->table('deleted_podcasts')->insert($podcast);

        // 4. Hard delete from the main table
        // (Using Query Builder directly bypasses CI4 soft-delete if it was enabled on the model)
        $db->table('podcasts')->where('id', $id)->delete();

        // Optional: You could also run a query here to delete/archive comments 
        // linked to this podcast if you have a comments table.

        // Complete the transaction
        $db->transComplete();

        // 5. Check if the transfer was successful
        if ($db->transStatus() === false) {
            return redirect()->to('admin/podcasts')->with('error', 'A database error occurred while trying to archive the podcast.');
        }

        return redirect()->to('admin/podcasts')->with('success', 'Podcast archived and deleted successfully.');
    }

    public function save_old_beforeHLS_encoding($id = null)
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

    /**
     * Generates a clean, unique slug for a podcast.
     * Example: "The Holy Spirit" -> "the-holy-spirit"
     * If taken: "the-holy-spirit-1", "the-holy-spirit-2"
     */
    private function generateUniqueSlug(string $title): string
    {
        // 1. Convert title to lowercase and replace spaces/special chars with dashes
        $baseSlug = strtolower(url_title($title, '-', true));
        $slug = $baseSlug;
        $counter = 1;

        // 2. Keep checking the database until we find a slug that does NOT exist
        // (If the DB rollback happened previously, this will safely return the base slug!)
        while ($this->podcastModel->where('slug', $slug)->first()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
    
    public function save($id = null)
    {

    if ($id && $this->request->getPost('action') === 'upload_only_cover') {
            $coverFile = $this->request->getFile('cover_image');
            
            if ($coverFile && $coverFile->isValid()) {
                $podcast = $this->podcastModel->find($id);
                
                if ($podcast) {
                    $cloudflare = new \App\Libraries\CloudflareStorage();
                    $coverUrl = $cloudflare->optimizeAndUpload($coverFile, 'podcasts/covers', $podcast['slug'], $podcast['cover_image_url']);
                    
                    if ($coverUrl) {
                        // Update ONLY the cover image URL. No transactions needed!
                        $this->podcastModel->update($id, ['cover_image_url' => $coverUrl]);
                    }
                }
            }
            
            // Exit immediately and return success to the frontend
            return $this->response->setJSON(['success' => true, 'redirect' => site_url('admin/podcasts')]);
        }
        $db = \Config\Database::connect();
        $db->transStart(); 

        $title = trim((string) $this->request->getPost('title'));

        // Validate immediately
        if ($title === '') {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'The podcast title is strictly required.']);
            }
            return redirect()->back()->withInput()->with('error', 'The podcast title is strictly required.');
        }
        
        $slug = '';
        $podcastData = [
            'title'       => $title,
            'description' => $this->request->getPost('description'),
            'category_id' => $this->request->getPost('category_id'),
            'theme_id'    => $this->request->getPost('theme_id') ?: null,
            'podcast_date' => $this->request->getPost('podcast_date') ?: null,
        ];

        $oldPodcast = null;
        if ($id) {
            $oldPodcast = $this->podcastModel->find($id);
            $slug = $oldPodcast['slug'];
        } else {
            $slug = $this->generateUniqueSlug($title);
            $podcastData['slug'] = $slug;
            $podcastData['ffmeg_status'] = 'processing';
            $podcastData['created_by'] = session()->get('user_id');
            $podcastData['published_at'] = $this->request->getPost('status') === 'published' ? date('Y-m-d H:i:s') : null;
            $podcastData['status']      = $this->request->getPost('status');
        }

        // --------------------------------------------------------------------
        // 1. CLOUDFLARE R2: Handle Cover Image ONLY on Update
        // --------------------------------------------------------------------
        if ($id) {
            $coverFile = $this->request->getFile('cover_image');
            if ($coverFile && $coverFile->isValid()) {
                $cloudflare = new \App\Libraries\CloudflareStorage();
                $oldCoverUrl = $oldPodcast['cover_image_url'] ?? null;
                
                $coverUrl = $cloudflare->optimizeAndUpload($coverFile, 'podcasts/covers', $slug, $oldCoverUrl);
                
                if ($coverUrl) {
                    $podcastData['cover_image_url'] = $coverUrl;
                }
                // Notice: No rollback or error response here! 
                // If it fails, the process just continues silently.
            }
        }

        // --------------------------------------------------------------------
        // 2. MP3 Upload (ONLY FOR NEW PODCASTS)
        // --------------------------------------------------------------------
        if (!$id) {
            $highFile = $this->request->getFile('media_high');
            if ($highFile && $highFile->isValid() && !$highFile->hasMoved()) {
                $vaultDir = WRITEPATH . 'uploads/vault/';
                if (!is_dir($vaultDir)) mkdir($vaultDir, 0777, true);
                
                // Save it locally instantly
                $highFile->move($vaultDir, $slug . '.mp3');
            }
        }

        // --------------------------------------------------------------------
        // 3. Database Updates & Job Queuing
        // --------------------------------------------------------------------
        if ($id) {
            $this->podcastModel->update($id, $podcastData);
            $podcastId = $id;
        } else {
            $podcastId = $this->podcastModel->insert($podcastData);

            $lastJob = $db->table('media_queue')
                          ->whereIn('status', ['pending', 'processing'])
                          ->orderBy('start_time', 'DESC')
                          ->get()
                          ->getRow();

            $startTime = date('Y-m-d H:i:s'); 

            if ($lastJob && $lastJob->start_time) {
                $lastJobTime = strtotime($lastJob->start_time);
                $currentTime = time();
                $nextAvailableTime = $lastJobTime + (20 * 60); 
                if ($nextAvailableTime > $currentTime) {
                    $startTime = date('Y-m-d H:i:s', $nextAvailableTime);
                }
            }
            
            $db->table('media_queue')->insert([
                'podcast_id' => $podcastId,
                'slug'       => $slug,
                'status'     => 'pending',
                'start_time' => $startTime
            ]);
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

        session()->setFlashdata('success', $id ? 'Podcast updated successfully!' : 'Podcast published successfully!');
        
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true, 
                'redirect' => site_url('admin/podcasts'),
                'podcast_id' => $podcastId, // Required for step 2!
                'new_csrf' => csrf_hash()   // Required to pass the CI4 security check on step 2!
            ]);
        }
        return redirect()->to('admin/podcasts');
    }
     public function save_old_before_removing_cover_image_from_podcast_create($id = null)
    {
        $db = \Config\Database::connect();
        $db->transStart(); 

       $title = trim((string) $this->request->getPost('title'));

        // 2. Validate immediately before touching the database!
        if ($title === '') {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'The podcast title is strictly required.']);
            }
            return redirect()->back()->withInput()->with('error', 'The podcast title is strictly required.');
        }
        $slug = '';
        $podcastData = [
            'title'       => $title,
            'description' => $this->request->getPost('description'),
            'category_id' => $this->request->getPost('category_id'),
            'theme_id'    => $this->request->getPost('theme_id') ?: null,
        ];

        $oldPodcast = null;
        if ($id) {
            $oldPodcast = $this->podcastModel->find($id);
            $slug = $oldPodcast['slug'];
        } else {
            $slug = $this->generateUniqueSlug($title);
            $podcastData['slug'] = $slug;
            $podcastData['ffmeg_status'] = 'processing';
            $podcastData['created_by'] = session()->get('user_id');
            $podcastData['published_at'] = $this->request->getPost('status') === 'published' ? date('Y-m-d H:i:s') : null;
            $podcastData['status']      = $this->request->getPost('status');
        }

        $cloudflare = new \App\Libraries\CloudflareStorage();

        // --------------------------------------------------------------------
        // 1. CLOUDFLARE R2: Handle Cover Image (For Both New & Updates)
        // --------------------------------------------------------------------
        $coverFile = $this->request->getFile('cover_image');
        if ($coverFile && $coverFile->isValid()) {
            
            $cloudflare = new \App\Libraries\CloudflareStorage();
            $oldCoverUrl = $oldPodcast['cover_image_url'] ?? null;
            
            // 👉 ADJUSTMENT 2: Use the ultimate DRY uploader to automatically compress and replace!
            $coverUrl = $cloudflare->optimizeAndUpload($coverFile, 'podcasts/covers', $slug, $oldCoverUrl);
            
            if ($coverUrl) {
                $podcastData['cover_image_url'] = $coverUrl;
            } else {
                $db->transRollback();
                // 👉 ADJUSTMENT 3: Return JSON so your JS Wizard catches the error beautifully
                return $this->response->setJSON(['success' => false, 'message' => 'Failed to optimize and upload cover image....']);
            }
        }

        // --------------------------------------------------------------------
        // 2. CLOUDFLARE R2: Handle MP3 Uploads (ONLY FOR NEW PODCASTS)
        // --------------------------------------------------------------------
        if (!$id) {

            // High Quality MP3 (Required)
            $highFile = $this->request->getFile('media_high');


   if ($highFile && $highFile->isValid() && !$highFile->hasMoved()) {
                $vaultDir = WRITEPATH . 'uploads/vault/';
                if (!is_dir($vaultDir)) mkdir($vaultDir, 0777, true);
                
                // Save it locally as slug.mp3
                $highFile->move($vaultDir, $slug . '.mp3');
            }
      
        }

        // --------------------------------------------------------------------
        // 3. Database Updates
        // --------------------------------------------------------------------
        if ($id) {
            $this->podcastModel->update($id, $podcastData);
            $podcastId = $id;
        } else {
            // 3. Drop the job into the Waiting Room!
           
            $podcastId = $this->podcastModel->insert($podcastData);

            // ==========================================
            // JOB THROTTLING: Calculate the next safe start time
            // ==========================================
            $db = \Config\Database::connect();
            
            // Find the most recently scheduled job (pending or processing)
            $lastJob = $db->table('media_queue')
                          ->whereIn('status', ['pending', 'processing'])
                          ->orderBy('start_time', 'DESC')
                          ->get()
                          ->getRow();

            $startTime = date('Y-m-d H:i:s'); // Default: Start immediately!

            if ($lastJob && $lastJob->start_time) {
                $lastJobTime = strtotime($lastJob->start_time);
                $currentTime = time();
                
                // Add a 20-minute "breathing gap" to the last job
                $nextAvailableTime = $lastJobTime + (20 * 60); 
                
                // If the next available slot is in the future, use it. 
                // Otherwise, the queue has been empty for a while, so start now.
                if ($nextAvailableTime > $currentTime) {
                    $startTime = date('Y-m-d H:i:s', $nextAvailableTime);
                }
            }
            // Drop the job into the Waiting Room with its scheduled time
            $db->table('media_queue')->insert([
                'podcast_id' => $podcastId,
                'slug'       => $slug,
                'status'     => 'pending',
                'start_time' => $startTime
            ]);

            
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


    private function processAllMedia_old($file, $slug)
{
    helper('media');
    set_time_limit(300);
    // set_time_limit(3600); // 1 hour max execution time
    ini_set('memory_limit', '1024M');
    $ffmpegPath = env('ffmpeg.path', 'ffmpeg');

    // 1. Create a secure local sandbox for this specific upload
    $baseDir = WRITEPATH . 'uploads/processing_' . $slug . '/';
    $hlsHighDir = $baseDir . 'hls_high/';
    $hlsLowDir  = $baseDir . 'hls_low/';
    
    if (!is_dir($hlsHighDir)) mkdir($hlsHighDir, 0777, true);
    if (!is_dir($hlsLowDir)) mkdir($hlsLowDir, 0777, true);

    register_shutdown_function(function () use ($baseDir, $slug) {
            $error = error_get_last();
            // Check if the script died violently due to a Fatal Error (like Timeout)
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                
                log_message('error', "HARD KILL FATAL ERROR on $slug. Executing Ghost Cleanup.");

                // 1. Wipe local server garbage using our new global helper
                if (function_exists('delete_directory_safely')) {
                    delete_directory_safely($baseDir); 
                }

                // 2. Wipe Cloudflare R2 garbage
                try {
                    $cloudflare = new \App\Libraries\CloudflareStorage();
                    $cloudflare->delete("podcasts/raw/high/{$slug}.mp3");
                    $cloudflare->delete("podcasts/raw/low/{$slug}.mp3");
                    $cloudflare->deleteFolder("podcasts/hls/high/{$slug}/");
                    $cloudflare->deleteFolder("podcasts/hls/low/{$slug}/");
                } catch (\Exception $e) {
                    log_message('error', "Cloudflare Ghost Cleanup Failed: " . $e->getMessage());
                }
            }
        });

    // 2. MOVE THE ORIGINAL FILE HERE SAFELY
    // We name it 'original.mp3'. It retains 100% of its original quality.
    $originalMp3Local = $baseDir . 'original.mp3';
    $file->move($baseDir, 'original.mp3');

    $lowMp3Local = $baseDir . 'low_quality.mp3';
try {
    // Helper Function to execute FFmpeg safely
    $runFFmpeg = function($command) {
        exec($command . " 2>&1", $output, $returnCode);
        if ($returnCode !== 0) {
            log_message('error', '[FFmpeg Failed] ' . implode("\n", $output));
            throw new \Exception("Audio processing failed.");
        }
    };

    // --- FFMPEG CONVERSIONS ---
    
    // A. Generate Low Quality MP3 (64k)
    $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -codec:a libmp3lame -b:a 64k " . escapeshellarg($lowMp3Local));

    // B. Generate HLS High (128k Segments)
    $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 128k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsHighDir . "seg_%03d.ts") . " " . escapeshellarg($hlsHighDir . "index.m3u8"));

    // C. Generate HLS Low (64k Segments)
    $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 64k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsLowDir . "seg_%03d.ts") . " " . escapeshellarg($hlsLowDir . "index.m3u8"));

    // --- CLOUDFLARE UPLOAD LOGIC ---
    $s3Client = new \Aws\S3\S3Client([
        'region'      => 'auto',
        'endpoint'    => getenv('R2_ENDPOINT'),
        'version'     => 'latest',
        'credentials' => [
            'key'    => getenv('R2_ACCESS_KEY'),
            'secret' => getenv('R2_SECRET_KEY'),
        ],
        'http' => [
                'connect_timeout' => 30,
                'timeout'         => 300, 
            ]
    ]);
    
    $bucket = getenv('R2_BUCKET');

    // 1. UPLOAD THE 100% ORIGINAL MP3 (The Vault)
    $s3Client->putObject([
        'Bucket'      => $bucket,
        'Key'         => "podcasts/raw/high/{$slug}.mp3",
        'SourceFile'  => $originalMp3Local,
        'ContentType' => 'audio/mpeg'
    ]);

    // 2. Upload the Compressed MP3
    $s3Client->putObject([
        'Bucket'      => $bucket,
        'Key'         => "podcasts/raw/low/{$slug}.mp3",
        'SourceFile'  => $lowMp3Local,
        'ContentType' => 'audio/mpeg'
    ]);

    // 3. Upload HLS Folders
    $this->uploadDirectoryToR2($hlsHighDir, "podcasts/hls/high/{$slug}/", $s3Client);
    $this->uploadDirectoryToR2($hlsLowDir, "podcasts/hls/low/{$slug}/", $s3Client);

    // Cleanup: Erase everything off the local server to save disk space
    $this->recursiveDelete($baseDir);

    // Return all 4 paths to be saved in the database!
    return [
        'master_high' => "podcasts/raw/high/{$slug}.mp3",
        'master_low'  => "podcasts/raw/low/{$slug}.mp3",
        'hls_high'    => "podcasts/hls/high/{$slug}/index.m3u8",
        'hls_low'     => "podcasts/hls/low/{$slug}/index.m3u8",
    ];
   } catch (\Throwable $e) { // Catch Throwable to grab Fatal Errors and Exceptions!
        
        // ==========================================
        // THE PROFESSIONAL ROLLBACK INITIATED
        // ==========================================
        log_message('error', "Media Rollback Initiated for $slug. Reason: " . $e->getMessage());

        // 1. Delete Local Garbage
        $this->recursiveDelete($baseDir);

        // 2. Delete Remote Cloudflare Garbage
        $cloudflare = new \App\Libraries\CloudflareStorage();
        $cloudflare->delete("podcasts/raw/high/{$slug}.mp3");
        $cloudflare->delete("podcasts/raw/low/{$slug}.mp3");
        $cloudflare->deleteFolder("podcasts/hls/high/{$slug}/");
        $cloudflare->deleteFolder("podcasts/hls/low/{$slug}/");

        // 3. Re-throw the error so the main save() function knows to rollback the Database!
        throw new \Exception("Processing failed and rollback executed: " . $e->getMessage());
    }
}

private function processAllMedia($file, $slug)
    {
        // 0. Load our new global helper so it's available in memory
        helper('media');

        // 1. Give PHP enough time and memory for heavy audio processing
        set_time_limit(3600); // 1 hour max execution time
        // set_time_limit(300); // 1 hour max execution time

        ini_set('memory_limit', '1024M'); // 1 GB of RAM for FFmpeg streaming

        $ffmpegPath = env('ffmpeg.path', 'ffmpeg');

        $baseDir = WRITEPATH . 'uploads/processing_' . $slug . '/';
        $hlsHighDir = $baseDir . 'hls_high/';
        $hlsLowDir  = $baseDir . 'hls_low/';
        
        if (!is_dir($hlsHighDir)) mkdir($hlsHighDir, 0777, true);
        if (!is_dir($hlsLowDir)) mkdir($hlsLowDir, 0777, true);

        // ==========================================
        // THE GHOST CLEANUP (Catches Fatal Errors & Timeouts)
        // ==========================================
        register_shutdown_function(function () use ($baseDir, $slug) {
            $error = error_get_last();
            // Check if the script died violently due to a Fatal Error (like Timeout)
            if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                
                log_message('error', "HARD KILL FATAL ERROR on $slug. Executing Ghost Cleanup.");

                // 1. Wipe local server garbage using our new global helper
                if (function_exists('delete_directory_safely')) {
                    delete_directory_safely($baseDir); 
                }

                // 2. Wipe Cloudflare R2 garbage
                try {
                    $cloudflare = new \App\Libraries\CloudflareStorage();
                    $cloudflare->delete("podcasts/raw/high/{$slug}.mp3");
                    $cloudflare->delete("podcasts/raw/low/{$slug}.mp3");
                    $cloudflare->deleteFolder("podcasts/hls/high/{$slug}/");
                    $cloudflare->deleteFolder("podcasts/hls/low/{$slug}/");
                } catch (\Exception $e) {
                    log_message('error', "Cloudflare Ghost Cleanup Failed: " . $e->getMessage());
                }
            }
        });
        // ==========================================

        $originalMp3Local = $baseDir . 'original.mp3';
        $lowMp3Local = $baseDir . 'low_quality.mp3';

        // Move original file to safety
        $file->move($baseDir, 'original.mp3');

        try {
            // Helper Function to execute FFmpeg safely
            $runFFmpeg = function($command) {
                exec($command . " 2>&1", $output, $returnCode);
                if ($returnCode !== 0) {
                    log_message('error', '[FFmpeg Failed] ' . implode("\n", $output));
                    throw new \Exception("Audio processing failed during FFmpeg conversion.");
                }
            };

            // --- FFMPEG CONVERSIONS ---
            $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -codec:a libmp3lame -b:a 64k " . escapeshellarg($lowMp3Local));
            $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 128k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsHighDir . "seg_%03d.ts") . " " . escapeshellarg($hlsHighDir . "index.m3u8"));
            $runFFmpeg("{$ffmpegPath} -i " . escapeshellarg($originalMp3Local) . " -c:a aac -b:a 64k -f hls -hls_time 10 -hls_playlist_type vod -hls_segment_filename " . escapeshellarg($hlsLowDir . "seg_%03d.ts") . " " . escapeshellarg($hlsLowDir . "index.m3u8"));

            // --- CLOUDFLARE UPLOAD LOGIC ---
            $s3Client = new \Aws\S3\S3Client([
                'region'      => 'auto',
                'endpoint'    => getenv('R2_ENDPOINT'),
                'version'     => 'latest',
                'credentials' => [
                    'key'    => getenv('R2_ACCESS_KEY'),
                    'secret' => getenv('R2_SECRET_KEY'),
                ],
                'http' => [
                    'connect_timeout' => 30,
                    'timeout'         => 300, 
                ]
            ]);
            
            $bucket = getenv('R2_BUCKET');

            // Upload single files
            $s3Client->putObject(['Bucket' => $bucket, 'Key' => "podcasts/raw/high/{$slug}.mp3", 'SourceFile' => $originalMp3Local, 'ContentType' => 'audio/mpeg']);
            $s3Client->putObject(['Bucket' => $bucket, 'Key' => "podcasts/raw/low/{$slug}.mp3", 'SourceFile' => $lowMp3Local, 'ContentType' => 'audio/mpeg']);

            // Upload Folders
            $this->uploadDirectoryToR2($hlsHighDir, "podcasts/hls/high/{$slug}/", $s3Client);
            $this->uploadDirectoryToR2($hlsLowDir, "podcasts/hls/low/{$slug}/", $s3Client);

            // Success Cleanup! Wipe local files using the global helper.
            delete_directory_safely($baseDir);

            return [
                'master_high' => "podcasts/raw/high/{$slug}.mp3",
                'master_low'  => "podcasts/raw/low/{$slug}.mp3",
                'hls_high'    => "podcasts/hls/high/{$slug}/index.m3u8",
                'hls_low'     => "podcasts/hls/low/{$slug}/index.m3u8",
            ];

        } catch (\Throwable $e) { 
            
            // ==========================================
            // THE STANDARD ROLLBACK
            // ==========================================
            log_message('error', "Media Rollback Initiated for $slug. Reason: " . $e->getMessage());

            // 1. Delete Local Garbage using the global helper
            delete_directory_safely($baseDir);

            // 2. Delete Remote Cloudflare Garbage
            $cloudflare = new \App\Libraries\CloudflareStorage();
            $cloudflare->delete("podcasts/raw/high/{$slug}.mp3");
            $cloudflare->delete("podcasts/raw/low/{$slug}.mp3");
            $cloudflare->deleteFolder("podcasts/hls/high/{$slug}/");
            $cloudflare->deleteFolder("podcasts/hls/low/{$slug}/");

            // 3. Re-throw the error so the database rolls back
            throw new \Exception("Processing failed and rollback executed: " . $e->getMessage());
        }
    }

private function uploadDirectoryToR2($dir, $r2Path, $client) {
    foreach (glob($dir . "*") as $file) {
        $client->putObject([
            'Bucket'      => getenv('R2_BUCKET'),
            'Key'         => $r2Path . basename($file),
            'SourceFile'  => $file,
            'ContentType' => (pathinfo($file, PATHINFO_EXTENSION) === 'm3u8') ? 'application/x-mpegURL' : 'video/MP2T'
        ]);
    }
}

/**
 * Ensures the temporary server folder is completely wiped out
 */
private function recursiveDelete($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
                    $this->recursiveDelete($dir. DIRECTORY_SEPARATOR .$object);
                else
                    unlink($dir. DIRECTORY_SEPARATOR .$object);
            }
        }
        rmdir($dir);
    }
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
    public function media_old($id)
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
    public function updateMedia_old($id)
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

    public function media($id)
    {
        $podcast = $this->podcastModel->find($id);
        if (!$podcast) {
            return redirect()->to('admin/podcasts')->with('error', 'Podcast not found.');
        }

        // We pass the Master MP3 URLs to the view so the HTML5 audio player can stream them
        $data = [
            'title'   => 'Manage Audio: ' . $podcast['title'],
            'podcast' => $podcast,
            'highUrl' => !empty($podcast['master_high_url']) ? secure_audio_url($podcast['master_high_url']) : '',
            'lowUrl'  => !empty($podcast['master_low_url']) ? secure_audio_url($podcast['master_low_url']) : ''
        ];

        return view('admin/podcasts/media', $data);
    }

    public function updateMedia($id)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setBody('Direct access not allowed');
        }

        $podcast = $this->podcastModel->find($id);
        if (!$podcast) {
            return $this->response->setJSON(['success' => false, 'message' => 'Podcast not found.']);
        }

        $highFile = $this->request->getFile('media_high');
        if (!$highFile || !$highFile->isValid() || $highFile->hasMoved()) {
            return $this->response->setJSON(['success' => false, 'message' => 'A valid High Quality MP3 file is required.']);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $slug = $podcast['slug'];

        // 1. Move the new file to the local Vault (Overwriting the old one)
        $vaultDir = WRITEPATH . 'uploads/vault/';
        if (!is_dir($vaultDir)) mkdir($vaultDir, 0777, true);
        $highFile->move($vaultDir, $slug . '.mp3', true); // 'true' forces overwrite

try {
            $cloudflare = new \App\Libraries\CloudflareStorage();
            
            // Delete the raw MP3 files using the exact paths from the database
            if (!empty($podcast['master_high_url'])) {
                $cloudflare->delete($podcast['master_high_url']);
            }
            if (!empty($podcast['master_low_url'])) {
                $cloudflare->delete($podcast['master_low_url']);
            }
            
            // Delete the entire HLS folders using the structure from your Cron Job
            // (Assuming you have this deleteFolder method in your CloudflareStorage library)
            if (!empty($podcast['media_high_url']) || !empty($podcast['media_low_url'])) {
                $cloudflare->deleteFolder("podcasts/hls/high/{$slug}/");
                $cloudflare->deleteFolder("podcasts/hls/low/{$slug}/");
            }
        } catch (\Exception $e) {
            // We wrap this in a try/catch so that if Cloudflare has a network hiccup, 
            // it logs the error but DOES NOT crash the user's upload process!
            log_message('error', "Failed to delete old R2 media for {$slug}: " . $e->getMessage());
        }


        // 2. Reset the Podcast Statuses back to square one!
        // We set ffmeg_status to 'idle' so the cron job knows it needs to process it.
        $db->table('podcasts')->where('id', $id)->update([
            'status'       => 'processing',
            'ffmeg_status' => 'idle',
            'published_at' => null, // Un-publish it until it passes QA again
            'review_count' => 0  ,
            'master_high_url' => null, // Wipe the old Master HQ
            'master_low_url'  => null, // Wipe the old Master LQ
            'media_high_url'  => null, // Wipe the old HLS HQ
            'media_low_url'   => null   // Reset approvals
        ]);

        // 3. Clear old QA Reviews since the audio changed entirely
        $db->table('podcast_reviews')->where('podcast_id', $id)->delete();
        $db->table('media_queue')
           ->where('podcast_id', $id)
           ->whereIn('status', ['pending', 'processing', 'failed'])
           ->update([
               'status'    => 'cancelled',
               'error_log' => 'Cancelled: The user uploaded a replacement audio file.'
           ]);

        // 4. Job Throttling: Calculate the next safe start time
        $lastJob = $db->table('media_queue')
                      ->whereIn('status', ['pending', 'processing'])
                      ->orderBy('start_time', 'DESC')
                      ->get()
                      ->getRow();

        $startTime = date('Y-m-d H:i:s'); // Default: Start immediately

        if ($lastJob && $lastJob->start_time) {
            $lastJobTime = strtotime($lastJob->start_time);
            $currentTime = time();
            $nextAvailableTime = $lastJobTime + (20 * 60); // 20-minute gap
            if ($nextAvailableTime > $currentTime) {
                $startTime = date('Y-m-d H:i:s', $nextAvailableTime);
            }
        }

        // 5. Insert the new job into the queue
        $db->table('media_queue')->insert([
            'podcast_id' => $id,
            'slug'       => $slug,
            'status'     => 'pending',
            'start_time' => $startTime
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON(['success' => false, 'message' => 'Database error during update.']);
        }

        session()->setFlashdata('success', 'New audio queued! The podcast has been reverted to Processing status.');
        return $this->response->setJSON(['success' => true, 'redirect' => site_url('admin/podcasts')]);
    }




}