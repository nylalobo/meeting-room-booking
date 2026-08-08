<?php

namespace App\Models;

use CodeIgniter\Model;

class CateringRequest extends Model
{
    protected $table            = 'catering_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'requested_by',
        'catering_type',
        'quantity',
        'special_requirements',
        'status',
        'requested_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'booking_id'           => 'required|integer',
        'requested_by'        => 'required|integer',
        'catering_type'       => 'required|max_length[100]',
        'quantity'            => 'required|integer|greater_than[0]',
        'status'              => 'required|in_list[pending,approved,rejected,cancelled,completed]',
        'requested_at'        => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    protected $validationMessages = [
        'booking_id' => [
            'required' => 'Booking is required.',
            'integer'  => 'Booking ID must be a valid number.',
        ],
        'requested_by' => [
            'required' => 'Requesting user is required.',
            'integer'  => 'User ID must be a valid number.',
        ],
        'catering_type' => [
            'required'   => 'Catering type is required.',
            'max_length' => 'Catering type cannot exceed 100 characters.',
        ],
        'quantity' => [
            'required'     => 'Catering quantity is required.',
            'integer'      => 'Quantity must be a valid number.',
            'greater_than' => 'Quantity must be greater than zero.',
        ],
        'status' => [
            'required' => 'Catering request status is required.',
            'in_list'  => 'Invalid catering request status.',
        ],
    ];
}