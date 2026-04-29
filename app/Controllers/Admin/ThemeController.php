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

    public function save($id = null)
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

    public function delete($id)
    {
        $this->themeModel->delete($id);
        return redirect()->to('admin/themes')->with('success', 'Theme deleted.');
    }
}