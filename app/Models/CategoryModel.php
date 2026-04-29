<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table            = 'categories';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['name', 'slug', 'icon_url'];
    protected $useTimestamps    = true;

    // Built-in CI4 validation rules!
    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[100]',
        'slug' => 'required|is_unique[categories.slug,id,{id}]'
    ];
}