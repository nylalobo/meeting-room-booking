<?php

namespace App\Models;

use CodeIgniter\Model;

class Booking extends Model
{
    protected $table            = 'bookings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'room_id',
        'user_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'room_id'    => 'required|integer',
        'user_id'    => 'required|integer',
        'title'      => 'required|max_length[200]',
        'start_time' => 'required|valid_date[Y-m-d H:i:s]',
        'end_time'   => 'required|valid_date[Y-m-d H:i:s]',
        'status'     => 'required|in_list[pending,approved,rejected,cancelled,completed]',
    ];

    protected $validationMessages = [
        'room_id' => [
            'required' => 'Room is required.',
            'integer'  => 'Room ID must be a valid number.',
        ],
        'user_id' => [
            'required' => 'User is required.',
            'integer'  => 'User ID must be a valid number.',
        ],
        'title' => [
            'required'   => 'Booking title is required.',
            'max_length' => 'Booking title cannot exceed 200 characters.',
        ],
        'start_time' => [
            'required' => 'Start time is required.',
        ],
        'end_time' => [
            'required' => 'End time is required.',
        ],
        'status' => [
            'required' => 'Booking status is required.',
            'in_list'  => 'Invalid booking status.',
        ],
    ];
}