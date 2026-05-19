<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;

class CategoryController extends BaseController
{
    protected $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
        helper(['form', 'url', 'text']); // Text helper gives us url_title() for slugs
    }

    // 1. READ: Display the data table
    public function index()
    {
        $data = [
            'title'      => 'Manage Categories',
            'categories' => $this->categoryModel->findAll()
        ];
        return view('admin/categories/index', $data);
    }

    // 2. CREATE/UPDATE VIEW: Show the form
    public function form($id = null)
    {
        $data = [
            'title'    => $id ? 'Edit Category' : 'Add New Category',
            'category' => $id ? $this->categoryModel->find($id) : null
        ];
        return view('admin/categories/form', $data);
    }

    // 3. PROCESS: Handle the form submission and Image Upload
    public function save_old($id = null)
    {
        $name = $this->request->getPost('name');
        
        $saveData = [
            'name' => $name,
            'slug' => strtolower(url_title($name)) // Auto-generates the slug!
        ];

        // Handle the Icon Upload securely
        $file = $this->request->getFile('icon');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            // Saves to public/uploads/categories
            $file->move(FCPATH . 'uploads/categories', $newName); 
            $saveData['icon_url'] = 'uploads/categories/' . $newName;
        }

        // Update if ID exists, otherwise Insert
        if ($id) {
            $this->categoryModel->update($id, $saveData);
            $message = 'Category updated successfully.';
        } else {
            $this->categoryModel->insert($saveData);
            $message = 'Category created successfully.';
        }

        if ($this->categoryModel->errors()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->categoryModel->errors()));
        }

        return redirect()->to('admin/categories')->with('success', $message);
    }

    // 4. DELETE
    public function delete_old($id)
    {
        $this->categoryModel->delete($id);
        return redirect()->to('admin/categories')->with('success', 'Category deleted.');
    }

    public function save($id = null)
    {
        $name = $this->request->getPost('name');
        
        $saveData = [
            'name' => $name,
            // 'slug' => strtolower(url_title($name)) // Auto-generates the slug!
        ];
        $oldCategory = $id ? $this->categoryModel->find($id) : null;
        if (!$id) {
            // $saveData['slug'] = strtolower(url_title($name));
            $slug = strtolower(url_title($name));
            $saveData['slug'] = $slug;
        }else{
            $slug = $oldCategory['slug'] ?? strtolower(url_title($name));
        }

        // Fetch the OLD category data if updating
        $oldCategory = $id ? $this->categoryModel->find($id) : null;

        // --------------------------------------------------------------------
        // CLOUDFLARE R2: Handle Icon Upload & Deletion
        // --------------------------------------------------------------------
        // $file = $this->request->getFile('icon');
        // if ($file && $file->isValid() && !$file->hasMoved()) {
            
        //     $cloudflare = new \App\Libraries\CloudflareStorage();
        //     $iconUrl = $cloudflare->upload($file, 'categories/icons',$slug); 
            
        //     if ($iconUrl) {
        //         $saveData['icon_url'] = $iconUrl;
                
        //         // If updating, delete the OLD icon from Cloudflare
        //         if ($oldCategory && !empty($oldCategory['icon_url'])) {
        //             $cloudflare->delete($oldCategory['icon_url']);
        //         }
        //     } else {
        //         return redirect()->back()->withInput()->with('error', 'Failed to upload category icon to the cloud.');
        //     }
        // }
        // --------------------------------------------------------------------
        // COMPRESSION & UPLOAD LOGIC
        // --------------------------------------------------------------------
        $file = $this->request->getFile('icon');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            
            // 1. Grab the temporary raw file from PHP
            $tempPath = $file->getTempName();
            
            // 2. Compress it to WebP (Max width 800px, 80% Quality)
            $optimizer = new \App\Libraries\ImageOptimizer();
            $optimizedPath = $optimizer->optimizeToWebp($tempPath, 800, 80);

            if ($optimizedPath) {
                $cloudflare = new \App\Libraries\CloudflareStorage();
                
                // 3. Upload the optimized version! Force the .webp extension on the slug.
                $newIconName = $slug . '.webp';
                $iconUrl = $cloudflare->uploadOptimized($optimizedPath, 'categories/icons', $newIconName); 
                
                if ($iconUrl) {
                    $saveData['icon_url'] = $iconUrl;
                    
                    // 4. Delete the old icon if the name changed (e.g., moving from .png to .webp)
                    if ($oldCategory && !empty($oldCategory['icon_url']) && $oldCategory['icon_url'] !== $iconUrl) {
                        $cloudflare->delete($oldCategory['icon_url']);
                    }
                } else {
                    return redirect()->back()->withInput()->with('error', 'Failed to upload optimized icon to the cloud.');
                }
                
                // 5. IMPORTANT: Delete the local temp optimized file so it doesn't clog your server!
                unlink($optimizedPath);
            } else {
                return redirect()->back()->withInput()->with('error', 'Failed to optimize the image. Unsupported format.');
            }
        }

        // Update if ID exists, otherwise Insert
        if ($id) {
            $this->categoryModel->update($id, $saveData);
            $message = 'Category updated successfully.';
        } else {
            $this->categoryModel->insert($saveData);
            $message = 'Category created successfully.';
        }

        if ($this->categoryModel->errors()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->categoryModel->errors()));
        }

        return redirect()->to('admin/categories')->with('success', $message);
    }

    public function delete($id)
    {
        // 1. Fetch the category BEFORE deleting it
        $category = $this->categoryModel->find($id);
        
        if ($category) {
            // 2. Wipe the icon from Cloudflare
            if (!empty($category['icon_url'])) {
                $cloudflare = new \App\Libraries\CloudflareStorage();
                $cloudflare->delete($category['icon_url']);
            }
            
            // 3. Delete the database record
            $this->categoryModel->delete($id);
        }

        return redirect()->to('admin/categories')->with('success', 'Category and its icon successfully deleted.');
    }
}