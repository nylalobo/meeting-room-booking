<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingParticipant extends Model
{
    protected $table            = 'booking_participants';
    protected $primaryKey       = 'booking_id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'user_id',
        'participant_type',
        'response_status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'booking_id'       => 'required|integer',
        'user_id'          => 'required|integer',
        'participant_type' => 'required|in_list[organizer,participant,guest]',
        'response_status'  => 'required|in_list[pending,accepted,declined,tentative]',
    ];

    protected $validationMessages = [
        'booking_id' => [
            'required' => 'Booking is required.',
            'integer'  => 'Booking ID must be a valid number.',
        ],

        'user_id' => [
            'required' => 'User is required.',
            'integer'  => 'User ID must be a valid number.',
        ],

        'participant_type' => [
            'required' => 'Participant type is required.',
            'in_list'  => 'Invalid participant type.',
        ],

        'response_status' => [
            'required' => 'Response status is required.',
            'in_list'  => 'Invalid response status.',
        ],
    ];
}