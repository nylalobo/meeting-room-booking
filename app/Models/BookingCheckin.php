<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingCheckin extends Model
{
    protected $table            = 'booking_checkins';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'user_id',
        'check_in_time',
        'check_out_time',
        'check_in_method',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'booking_id'      => 'required|is_natural_no_zero',
        'user_id'         => 'required|is_natural_no_zero',
        'check_in_time'   => 'required|valid_date[Y-m-d H:i:s]',
        'check_out_time'  => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'check_in_method' => 'permit_empty|in_list[qr_code,manual,room_display]',
        'status'          => 'permit_empty|in_list[checked_in,checked_out,auto_completed]',
    ];

    protected $validationMessages = [
        'booking_id' => [
            'required'           => 'Booking ID is required.',
            'is_natural_no_zero' => 'Booking ID must be a valid positive integer.',
        ],
        'user_id' => [
            'required'           => 'User ID is required.',
            'is_natural_no_zero' => 'User ID must be a valid positive integer.',
        ],
        'check_in_time' => [
            'required'   => 'Check-in time is required.',
            'valid_date' => 'Check-in time must be a valid datetime in Y-m-d H:i:s format.',
        ],
        'check_out_time' => [
            'valid_date' => 'Check-out time must be a valid datetime in Y-m-d H:i:s format.',
        ],
        'check_in_method' => [
            'in_list' => 'Check-in method must be one of: qr_code, manual, room_display.',
        ],
        'status' => [
            'in_list' => 'Status must be one of: checked_in, checked_out, auto_completed.',
        ],
    ];

    /**
     * Get active check-in for a specific booking.
     *
     * @param int $bookingId
     * @return array|null
     */
    public function getActiveCheckinForBooking(int $bookingId): ?array
    {
        return $this->where('booking_id', $bookingId)
            ->where('status', 'checked_in')
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Get active check-in for a specific user and booking.
     *
     * @param int $bookingId
     * @param int $userId
     * @return array|null
     */
    public function getActiveCheckin(int $bookingId, int $userId): ?array
    {
        return $this->where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->where('status', 'checked_in')
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Get the latest check-in record for a user and booking (regardless of status).
     *
     * @param int $bookingId
     * @param int $userId
     * @return array|null
     */
    public function getLatestCheckinForUser(int $bookingId, int $userId): ?array
    {
        return $this->where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * Get all check-ins for a booking with user details.
     *
     * @param int $bookingId
     * @return array
     */
    public function getCheckinsWithUsers(int $bookingId): array
    {
        return $this->select('booking_checkins.*, users.first_name, users.last_name, users.email')
            ->join('users', 'users.id = booking_checkins.user_id', 'left')
            ->where('booking_checkins.booking_id', $bookingId)
            ->orderBy('booking_checkins.check_in_time', 'ASC')
            ->findAll();
    }
}
