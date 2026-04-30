<?php

namespace App\Models;

use CodeIgniter\Model;

class ThemeModel extends Model
{
    protected $table            = 'themes';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = ['name', 'slug', 'icon_url'];
    protected $useTimestamps    = true;

    // Built-in CI4 validation rules!
    protected $validationRules = [
        // 'id'   => 'permit_empty|is_natural_no_zero',
        'name' => 'required|min_length[3]|max_length[100]',
        'slug' => 'required|is_unique[themes.slug,id,{id}]'
    ];
}