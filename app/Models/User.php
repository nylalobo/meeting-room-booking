<?php

namespace App\Models;

use CodeIgniter\Model;

class User extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'department_id',
        'role_id',
        'first_name',
        'last_name',
        'email',
        'password_hash',
        'phone',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'id'            => 'permit_empty|is_natural_no_zero',
        'department_id' => 'permit_empty|integer',
        'role_id'       => 'permit_empty|integer',
        'first_name'    => 'required|max_length[100]',
        'last_name'     => 'required|max_length[100]',
        'email'         => 'required|valid_email|max_length[255]|is_unique[users.email,id,{id}]',
        'password_hash' => 'required',
        'phone'         => 'permit_empty|max_length[30]',
        'is_active'     => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'first_name' => [
            'required' => 'First name is required.',
        ],
        'last_name' => [
            'required' => 'Last name is required.',
        ],
        'email' => [
            'required'    => 'Email address is required.',
            'valid_email' => 'Please provide a valid email address.',
            'is_unique'   => 'This email address is already registered.',
        ],
        'password_hash' => [
            'required' => 'Password hash is required.',
        ],
    ];
}