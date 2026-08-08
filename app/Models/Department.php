<?php

namespace App\Models;

use CodeIgniter\Model;

class Department extends Model
{
    protected $table            = 'departments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'name',
        'description',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => 'required|max_length[100]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Department name is required.',
            'max_length' => 'Department name cannot exceed 100 characters.',
        ],
    ];
}