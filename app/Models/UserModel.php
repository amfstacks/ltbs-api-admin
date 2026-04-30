<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    
    // STRICT DRY PRINCIPLE: We use soft deletes so relationships never break
    protected $useSoftDeletes   = true;
    protected $allowedFields    = ['first_name', 'last_name', 'email', 'password_hash', 'role', 'status', 'bio', 'avatar_url','reset_token', 'reset_expires_at'];

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}