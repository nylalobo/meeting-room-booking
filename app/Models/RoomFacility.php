<?php

namespace App\Models;

use CodeIgniter\Model;

class RoomFacility extends Model
{
    protected $table            = 'room_facilities';
    protected $primaryKey       = 'room_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'room_id',
        'facility_id',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'room_id'     => 'required|integer',
        'facility_id' => 'required|integer',
    ];

    protected $validationMessages = [
        'room_id' => [
            'required' => 'Room is required.',
            'integer'  => 'Room ID must be a valid number.',
        ],
        'facility_id' => [
            'required' => 'Facility is required.',
            'integer'  => 'Facility ID must be a valid number.',
        ],
    ];
}