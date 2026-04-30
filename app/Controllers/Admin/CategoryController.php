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
        if (!$id) {
            $saveData['slug'] = strtolower(url_title($name));
        }

        // Fetch the OLD category data if updating
        $oldCategory = $id ? $this->categoryModel->find($id) : null;

        // --------------------------------------------------------------------
        // CLOUDFLARE R2: Handle Icon Upload & Deletion
        // --------------------------------------------------------------------
        $file = $this->request->getFile('icon');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            
            $cloudflare = new \App\Libraries\CloudflareStorage();
            $iconUrl = $cloudflare->upload($file, 'categories/icons'); 
            
            if ($iconUrl) {
                $saveData['icon_url'] = $iconUrl;
                
                // If updating, delete the OLD icon from Cloudflare
                if ($oldCategory && !empty($oldCategory['icon_url'])) {
                    $cloudflare->delete($oldCategory['icon_url']);
                }
            } else {
                return redirect()->back()->withInput()->with('error', 'Failed to upload category icon to the cloud.');
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