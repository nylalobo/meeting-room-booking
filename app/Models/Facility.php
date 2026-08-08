<?php

namespace App\Models;

use CodeIgniter\Model;

class Facility extends Model
{
    protected $table            = 'facilities';
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
        'name'      => 'required|max_length[100]',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'Facility name is required.',
            'max_length' => 'Facility name cannot exceed 100 characters.',
        ],
    ];
}