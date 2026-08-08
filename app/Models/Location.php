<?php

namespace App\Models;

use CodeIgniter\Model;

class Location extends Model
{
    protected $table            = 'locations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'name',
        'address',
        'city',
        'state',
        'country',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => 'required|max_length[150]',
        'city' => 'permit_empty|max_length[100]',
        'state' => 'permit_empty|max_length[100]',
        'country' => 'permit_empty|max_length[100]',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'Location name is required.',
            'max_length' => 'Location name cannot exceed 150 characters.',
        ],
    ];
}