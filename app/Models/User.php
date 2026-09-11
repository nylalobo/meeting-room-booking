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
        'email_verified_at',
        'email_verification_token',
        'email_verification_expires_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'id'                            => 'permit_empty|is_natural_no_zero',
        'department_id'                 => 'permit_empty|integer',
        'role_id'                       => 'permit_empty|integer',
        'first_name'                    => 'required|max_length[100]',
        'last_name'                     => 'required|max_length[100]',
        'email'                         => 'required|valid_email|max_length[255]|is_unique[users.email,id,{id}]',
        'password_hash'                 => 'permit_empty',
        'phone'                         => 'permit_empty|max_length[30]',
        'is_active'                     => 'permit_empty|in_list[0,1]',
        'email_verified_at'             => 'permit_empty|valid_date',
        'email_verification_token'      => 'permit_empty|max_length[64]',
        'email_verification_expires_at' => 'permit_empty|valid_date',
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
    ];

    /**
     * Allowed role names for public self-service registration.
     */
    public const PUBLIC_REGISTRATION_ROLE_NAMES = [
        'Employee',
        'Intern',
        'Team Lead',
    ];

    public array $registrationRules = [
        'first_name'       => 'required|max_length[100]',
        'last_name'        => 'required|max_length[100]',
        'email'            => 'required|valid_email|max_length[255]',
        'phone'            => 'permit_empty|max_length[30]',
        'role_id'          => 'required|is_natural_no_zero',
        'department_id'    => 'permit_empty|is_natural_no_zero',
        'password'         => 'required|min_length[8]|regex_match[/[A-Z]/]|regex_match[/[0-9]/]',
        'password_confirm' => 'required|matches[password]',
    ];

    public array $registrationMessages = [
        'first_name' => [
            'required'   => 'First name is required.',
            'max_length' => 'First name cannot exceed 100 characters.',
        ],
        'last_name' => [
            'required'   => 'Last name is required.',
            'max_length' => 'Last name cannot exceed 100 characters.',
        ],
        'email' => [
            'required'    => 'Email address is required.',
            'valid_email' => 'Please provide a valid email address.',
            'is_unique'   => 'This email address is already registered.',
        ],
        'phone' => [
            'max_length' => 'Phone number cannot exceed 30 characters.',
        ],
        'role_id' => [
            'required'           => 'Please select a company role.',
            'is_natural_no_zero' => 'Please select a valid company role.',
        ],
        'department_id' => [
            'is_natural_no_zero' => 'Please select a valid department.',
        ],
        'password' => [
            'required'    => 'Password is required.',
            'min_length'  => 'Password must be at least 8 characters long.',
            'regex_match' => 'Password must contain at least one uppercase letter and one number.',
        ],
        'password_confirm' => [
            'required' => 'Please confirm your password.',
            'matches'  => 'Passwords do not match.',
        ],
    ];
}