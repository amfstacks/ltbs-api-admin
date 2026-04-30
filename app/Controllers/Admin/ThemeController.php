<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ThemeModel;

class ThemeController extends BaseController
{
    protected $themeModel;

    public function __construct()
    {
        $this->themeModel = new ThemeModel();
        helper(['form', 'url', 'text']);
    }

    public function index()
    {
        $data = [
            'title'  => 'Manage Themes',
            'themes' => $this->themeModel->findAll()
        ];
        return view('admin/themes/index', $data);
    }

    public function form($id = null)
    {
        $data = [
            'title' => $id ? 'Edit Theme' : 'Add New Theme',
            'theme' => $id ? $this->themeModel->find($id) : null
        ];
        return view('admin/themes/form', $data);
    }

    public function save_old($id = null)
    {
        $name = $this->request->getPost('name');
        
        $saveData = [
            'name' => $name,
            'slug' => strtolower(url_title($name))
        ];

        $file = $this->request->getFile('icon');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/themes', $newName); // Dedicated themes folder!
            $saveData['icon_url'] = 'uploads/themes/' . $newName;
        }

        if ($id) {
            $this->themeModel->update($id, $saveData);
            $message = 'Theme updated successfully.';
        } else {
            $this->themeModel->insert($saveData);
            $message = 'Theme created successfully.';
        }

        if ($this->themeModel->errors()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->themeModel->errors()));
        }

        return redirect()->to('admin/themes')->with('success', $message);
    }

    public function delete_old($id)
    {
        $this->themeModel->delete($id);
        return redirect()->to('admin/themes')->with('success', 'Theme deleted.');
    }


    public function save($id = null)
    {
        $name = $this->request->getPost('name');
        
        $saveData = [
            'name' => $name,
            // 'slug' => strtolower(url_title($name))
        ];
        if (!$id) {
            $saveData['slug'] = strtolower(url_title($name));
        }

        // Fetch the OLD theme data if updating, to prevent orphan files
        $oldTheme = $id ? $this->themeModel->find($id) : null;

        // --------------------------------------------------------------------
        // CLOUDFLARE R2: Handle Icon Upload & Deletion
        // --------------------------------------------------------------------
        $file = $this->request->getFile('icon');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            
            $cloudflare = new \App\Libraries\CloudflareStorage();
            $iconUrl = $cloudflare->upload($file, 'themes/icons'); // Uploads to a dedicated folder!
            
            if ($iconUrl) {
                $saveData['icon_url'] = $iconUrl;
                
                // If updating, delete the OLD icon from Cloudflare
                if ($oldTheme && !empty($oldTheme['icon_url'])) {
                    $cloudflare->delete($oldTheme['icon_url']);
                }
            } else {
                return redirect()->back()->withInput()->with('error', 'Failed to upload icon to the cloud.');
            }
        }

        if ($id) {

            $this->themeModel->update($id, $saveData);
            $message = 'Theme updated successfully.';
        } else {
            $this->themeModel->insert($saveData);
            $message = 'Theme created successfully.';
        }

        if ($this->themeModel->errors()) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->themeModel->errors()));
        }

        return redirect()->to('admin/themes')->with('success', $message);
    }

    public function delete($id)
    {
        // 1. Fetch the theme BEFORE deleting it from the database
        $theme = $this->themeModel->find($id);
        
        if ($theme) {
            // 2. Wipe the icon from Cloudflare to prevent orphans!
            if (!empty($theme['icon_url'])) {
                $cloudflare = new \App\Libraries\CloudflareStorage();
                $cloudflare->delete($theme['icon_url']);
            }
            
            // 3. Now it is safe to delete the database record
            $this->themeModel->delete($id);
        }

        return redirect()->to('admin/themes')->with('success', 'Theme and its icon successfully deleted.');
    }
}