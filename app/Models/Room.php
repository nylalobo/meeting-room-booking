<?php

namespace App\Models;

use CodeIgniter\Model;

class Room extends Model
{
    protected $table            = 'rooms';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'location_id',
        'name',
        'room_code',
        'capacity',
        'floor',
        'description',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'location_id' => 'required|integer',
        'name'        => 'required|max_length[150]',
        'room_code'   => 'required|max_length[50]',
        'capacity'    => 'required|integer|greater_than[0]',
        'floor'       => 'permit_empty|max_length[50]',
        'is_active'   => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'location_id' => [
            'required' => 'Location is required.',
            'integer'  => 'Location ID must be a valid number.',
        ],
        'name' => [
            'required'   => 'Room name is required.',
            'max_length' => 'Room name cannot exceed 150 characters.',
        ],
        'room_code' => [
            'required'   => 'Room code is required.',
            'max_length' => 'Room code cannot exceed 50 characters.',
        ],
        'capacity' => [
            'required'     => 'Room capacity is required.',
            'integer'      => 'Capacity must be a valid number.',
            'greater_than' => 'Room capacity must be greater than zero.',
        ],
    ];
}