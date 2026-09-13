<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingVisitor extends Model
{
    protected $table            = 'booking_visitors';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'full_name',
        'email',
        'phone',
        'company',
        'notes',
        'status',
        'check_in_time',
        'check_out_time',
        'check_in_method',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['cleanData'];
    protected $beforeUpdate = ['cleanData'];

    protected $validationRules = [
        'booking_id'      => 'required|is_natural_no_zero',
        'full_name'       => 'required|min_length[2]|max_length[150]',
        'email'           => 'required|valid_email|max_length[150]',
        'phone'           => 'permit_empty|max_length[30]',
        'company'         => 'permit_empty|max_length[150]',
        'notes'           => 'permit_empty|max_length[1000]',
        'status'          => 'permit_empty|in_list[expected,checked_in,checked_out,cancelled]',
        'check_in_method' => 'permit_empty|in_list[manual,qr_code,reception]',
        'check_in_time'   => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'check_out_time'  => 'permit_empty|valid_date[Y-m-d H:i:s]',
    ];

    protected $validationMessages = [
        'booking_id' => [
            'required'           => 'Booking ID is required.',
            'is_natural_no_zero' => 'Booking ID must be a positive integer.',
        ],
        'full_name' => [
            'required'   => 'Visitor full name is required.',
            'min_length' => 'Visitor full name must be at least 2 characters.',
            'max_length' => 'Visitor full name cannot exceed 150 characters.',
        ],
        'email' => [
            'required'    => 'Visitor email address is required.',
            'valid_email' => 'Please provide a valid email address.',
            'max_length'  => 'Visitor email cannot exceed 150 characters.',
        ],
        'phone' => [
            'max_length' => 'Phone number cannot exceed 30 characters.',
        ],
        'company' => [
            'max_length' => 'Company name cannot exceed 150 characters.',
        ],
        'notes' => [
            'max_length' => 'Notes cannot exceed 1000 characters.',
        ],
        'status' => [
            'in_list' => 'Status must be one of: expected, checked_in, checked_out, cancelled.',
        ],
        'check_in_method' => [
            'in_list' => 'Check-in method must be one of: manual, qr_code, reception.',
        ],
    ];

    /**
     * Clean and normalize data before inserting/updating.
     */
    protected function cleanData(array $data): array
    {
        if (isset($data['data']['email'])) {
            $data['data']['email'] = strtolower(trim((string) $data['data']['email']));
        }
        if (isset($data['data']['full_name'])) {
            $data['data']['full_name'] = trim(strip_tags((string) $data['data']['full_name']));
        }
        if (isset($data['data']['phone'])) {
            $val = trim(strip_tags((string) $data['data']['phone']));
            $data['data']['phone'] = $val !== '' ? $val : null;
        }
        if (isset($data['data']['company'])) {
            $val = trim(strip_tags((string) $data['data']['company']));
            $data['data']['company'] = $val !== '' ? $val : null;
        }
        if (isset($data['data']['notes'])) {
            $val = trim(strip_tags((string) $data['data']['notes']));
            $data['data']['notes'] = $val !== '' ? $val : null;
        }

        return $data;
    }

    /**
     * Get all visitors for a booking.
     *
     * @param int $bookingId
     * @param bool $includeCancelled
     * @return array
     */
    public function getVisitorsForBooking(int $bookingId, bool $includeCancelled = true): array
    {
        $builder = $this->where('booking_id', $bookingId);

        if (!$includeCancelled) {
            $builder->where('status !=', 'cancelled');
        }

        return $builder->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Get a specific visitor scoped strictly to both visitor_id and booking_id (IDOR protection).
     *
     * @param int $bookingId
     * @param int $visitorId
     * @return array|null
     */
    public function getVisitor(int $bookingId, int $visitorId): ?array
    {
        return $this->where('id', $visitorId)
            ->where('booking_id', $bookingId)
            ->first();
    }

    /**
     * Find duplicate visitor by normalized email on the same booking.
     *
     * @param int $bookingId
     * @param string $email
     * @param int|null $excludeVisitorId
     * @return array|null
     */
    public function findDuplicate(int $bookingId, string $email, ?int $excludeVisitorId = null): ?array
    {
        $normEmail = strtolower(trim($email));
        $builder = $this->where('booking_id', $bookingId)
            ->where('email', $normEmail);

        if ($excludeVisitorId !== null) {
            $builder->where('id !=', $excludeVisitorId);
        }

        return $builder->first();
    }

    /**
     * Get count of active/expected visitors (excluding cancelled) for a booking.
     *
     * @param int $bookingId
     * @return int
     */
    public function getActiveExpectedCount(int $bookingId): int
    {
        return $this->where('booking_id', $bookingId)
            ->where('status !=', 'cancelled')
            ->countAllResults();
    }
}
