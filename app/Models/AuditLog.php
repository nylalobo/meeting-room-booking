<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLog extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'user_id'    => 'permit_empty|integer',
        'action'     => 'required|max_length[100]',
        'table_name' => 'permit_empty|max_length[100]',
        'record_id'  => 'permit_empty|integer',
        'ip_address' => 'permit_empty|max_length[45]',
    ];

    protected $validationMessages = [
        'user_id' => [
            'integer' => 'User ID must be a valid number.',
        ],
        'action' => [
            'required'   => 'Audit action is required.',
            'max_length' => 'Action cannot exceed 100 characters.',
        ],
        'table_name' => [
            'max_length' => 'Table name cannot exceed 100 characters.',
        ],
        'record_id' => [
            'integer' => 'Record ID must be a valid number.',
        ],
        'ip_address' => [
            'max_length' => 'IP address cannot exceed 45 characters.',
        ],
    ];
}