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
        'rejection_reason',
        'requested_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'booking_id'           => 'required|integer',
        'requested_by'         => 'required|integer',
        'catering_type'        => 'required|max_length[100]',
        'quantity'             => 'required|integer|greater_than[0]',
        'status'               => 'required|in_list[pending,approved,rejected,cancelled,completed]',
        'rejection_reason'     => 'permit_empty|max_length[500]',
        'requested_at'         => 'permit_empty|valid_date[Y-m-d H:i:s]',
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
        'rejection_reason' => [
            'max_length' => 'Rejection reason cannot exceed 500 characters.',
        ],
    ];

    /**
     * Get all catering requests for a specific booking, enriched with requester details.
     *
     * @param int $bookingId
     * @return array
     */
    public function getByBookingId(int $bookingId): array
    {
        $builder = $this->builder();
        $builder->select('
            catering_requests.*,
            users.first_name as requester_first_name,
            users.last_name as requester_last_name,
            users.email as requester_email
        ')
        ->join('users', 'users.id = catering_requests.requested_by', 'left')
        ->where('catering_requests.booking_id', $bookingId)
        ->orderBy('catering_requests.id', 'ASC');

        $rows = $builder->get()->getResultArray();
        return array_map([$this, 'formatCateringRow'], $rows);
    }

    /**
     * Get a single catering request enriched with requester and booking details.
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $builder = $this->builder();
        $builder->select('
            catering_requests.*,
            users.first_name as requester_first_name,
            users.last_name as requester_last_name,
            users.email as requester_email,
            bookings.title as booking_title,
            bookings.room_id,
            bookings.start_time as booking_start_time,
            bookings.end_time as booking_end_time,
            bookings.status as booking_status,
            bookings.user_id as organizer_user_id,
            rooms.name as room_name,
            rooms.room_code
        ')
        ->join('users', 'users.id = catering_requests.requested_by', 'left')
        ->join('bookings', 'bookings.id = catering_requests.booking_id', 'left')
        ->join('rooms', 'rooms.id = bookings.room_id', 'left')
        ->where('catering_requests.id', $id);

        $row = $builder->get()->getRowArray();
        return $row ? $this->formatCateringRow($row) : null;
    }

    /**
     * Format a catering row for API output.
     *
     * @param array $r
     * @return array
     */
    public function formatCateringRow(array $r): array
    {
        $requesterName = null;
        if (!empty($r['requester_first_name']) || !empty($r['requester_last_name'])) {
            $requesterName = trim(($r['requester_first_name'] ?? '') . ' ' . ($r['requester_last_name'] ?? ''));
        }

        $r['id'] = (int) $r['id'];
        $r['booking_id'] = (int) $r['booking_id'];
        $r['requested_by'] = (int) $r['requested_by'];
        $r['quantity'] = (int) $r['quantity'];
        $r['requester_name'] = $requesterName;

        return $r;
    }
}