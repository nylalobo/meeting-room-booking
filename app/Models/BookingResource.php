<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingResource extends Model
{
    protected $table            = 'booking_resources';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'booking_id',
        'equipment_id',
        'status',
        'assigned_by',
        'checked_out_at',
        'checked_out_by',
        'returned_at',
        'returned_to',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['cleanData'];
    protected $beforeUpdate = ['cleanData'];

    protected $validationRules = [
        'booking_id'     => 'required|is_natural_no_zero',
        'equipment_id'   => 'required|is_natural_no_zero',
        'status'         => 'permit_empty|in_list[reserved,checked_out,returned,cancelled]',
        'assigned_by'    => 'permit_empty|is_natural_no_zero',
        'checked_out_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'checked_out_by' => 'permit_empty|is_natural_no_zero',
        'returned_at'    => 'permit_empty|valid_date[Y-m-d H:i:s]',
        'returned_to'    => 'permit_empty|is_natural_no_zero',
        'notes'          => 'permit_empty|max_length[1000]',
    ];

    protected $validationMessages = [
        'booking_id' => [
            'required'           => 'Booking ID is required.',
            'is_natural_no_zero' => 'Booking ID must be a positive integer.',
        ],
        'equipment_id' => [
            'required'           => 'Equipment ID is required.',
            'is_natural_no_zero' => 'Equipment ID must be a positive integer.',
        ],
        'status' => [
            'in_list' => 'Status must be one of: reserved, checked_out, returned, cancelled.',
        ],
        'assigned_by' => [
            'is_natural_no_zero' => 'Assigned by user ID must be a valid integer.',
        ],
        'checked_out_at' => [
            'valid_date' => 'Checked out at must be a valid datetime in Y-m-d H:i:s format.',
        ],
        'checked_out_by' => [
            'is_natural_no_zero' => 'Checked out by user ID must be a valid integer.',
        ],
        'returned_at' => [
            'valid_date' => 'Returned at must be a valid datetime in Y-m-d H:i:s format.',
        ],
        'returned_to' => [
            'is_natural_no_zero' => 'Returned to user ID must be a valid integer.',
        ],
        'notes' => [
            'max_length' => 'Notes cannot exceed 1000 characters.',
        ],
    ];

    /**
     * Clean and normalize data before inserting/updating.
     */
    protected function cleanData(array $data): array
    {
        if (isset($data['data']['status'])) {
            $data['data']['status'] = strtolower(trim((string) $data['data']['status']));
            if ($data['data']['status'] === '') {
                $data['data']['status'] = 'reserved';
            }
        }
        if (isset($data['data']['assigned_by'])) {
            $val = $data['data']['assigned_by'];
            $data['data']['assigned_by'] = (!empty($val) && (int) $val > 0) ? (int) $val : null;
        }
        if (isset($data['data']['checked_out_by'])) {
            $val = $data['data']['checked_out_by'];
            $data['data']['checked_out_by'] = (!empty($val) && (int) $val > 0) ? (int) $val : null;
        }
        if (isset($data['data']['returned_to'])) {
            $val = $data['data']['returned_to'];
            $data['data']['returned_to'] = (!empty($val) && (int) $val > 0) ? (int) $val : null;
        }
        if (isset($data['data']['checked_out_at'])) {
            $val = trim((string) $data['data']['checked_out_at']);
            $data['data']['checked_out_at'] = $val !== '' ? $val : null;
        }
        if (isset($data['data']['returned_at'])) {
            $val = trim((string) $data['data']['returned_at']);
            $data['data']['returned_at'] = $val !== '' ? $val : null;
        }
        if (isset($data['data']['notes'])) {
            $val = trim((string) $data['data']['notes']);
            $data['data']['notes'] = $val !== '' ? $val : null;
        }

        return $data;
    }

    /**
     * Get all resources assigned to a booking enriched with equipment details.
     *
     * @param int $bookingId
     * @return array
     */
    public function getResourcesForBooking(int $bookingId): array
    {
        $builder = $this->builder();
        $builder->select('
            booking_resources.*,
            equipment.name as equipment_name,
            equipment.code as equipment_code,
            equipment.category,
            equipment.model_number,
            equipment.serial_number,
            equipment.description as equipment_description,
            equipment.status as equipment_catalog_status,
            equipment.location_id,
            locations.name as location_name,
            equipment.default_room_id,
            rooms.name as default_room_name,
            rooms.room_code as default_room_code,
            assigned_user.first_name as assigned_first_name,
            assigned_user.last_name as assigned_last_name,
            checkout_user.first_name as checkout_first_name,
            checkout_user.last_name as checkout_last_name,
            returned_user.first_name as returned_first_name,
            returned_user.last_name as returned_last_name
        ')
        ->join('equipment', 'equipment.id = booking_resources.equipment_id', 'left')
        ->join('locations', 'locations.id = equipment.location_id', 'left')
        ->join('rooms', 'rooms.id = equipment.default_room_id', 'left')
        ->join('users as assigned_user', 'assigned_user.id = booking_resources.assigned_by', 'left')
        ->join('users as checkout_user', 'checkout_user.id = booking_resources.checked_out_by', 'left')
        ->join('users as returned_user', 'returned_user.id = booking_resources.returned_to', 'left')
        ->where('booking_resources.booking_id', $bookingId)
        ->orderBy('booking_resources.id', 'ASC');

        $rows = $builder->get()->getResultArray();
        return array_map([$this, 'formatResourceRow'], $rows);
    }

    /**
     * Get a single resource assignment for a booking (IDOR-safe).
     *
     * @param int $bookingId
     * @param int $resourceId
     * @return array|null
     */
    public function getResource(int $bookingId, int $resourceId): ?array
    {
        $builder = $this->builder();
        $builder->select('
            booking_resources.*,
            equipment.name as equipment_name,
            equipment.code as equipment_code,
            equipment.category,
            equipment.model_number,
            equipment.serial_number,
            equipment.description as equipment_description,
            equipment.status as equipment_catalog_status,
            equipment.location_id,
            locations.name as location_name,
            equipment.default_room_id,
            rooms.name as default_room_name,
            rooms.room_code as default_room_code,
            assigned_user.first_name as assigned_first_name,
            assigned_user.last_name as assigned_last_name,
            checkout_user.first_name as checkout_first_name,
            checkout_user.last_name as checkout_last_name,
            returned_user.first_name as returned_first_name,
            returned_user.last_name as returned_last_name
        ')
        ->join('equipment', 'equipment.id = booking_resources.equipment_id', 'left')
        ->join('locations', 'locations.id = equipment.location_id', 'left')
        ->join('rooms', 'rooms.id = equipment.default_room_id', 'left')
        ->join('users as assigned_user', 'assigned_user.id = booking_resources.assigned_by', 'left')
        ->join('users as checkout_user', 'checkout_user.id = booking_resources.checked_out_by', 'left')
        ->join('users as returned_user', 'returned_user.id = booking_resources.returned_to', 'left')
        ->where('booking_resources.booking_id', $bookingId)
        ->where('booking_resources.id', $resourceId);

        $row = $builder->get()->getRowArray();
        return $row ? $this->formatResourceRow($row) : null;
    }

    /**
     * Format a resource assignment row with clear types and related equipment info.
     *
     * @param array $r
     * @return array
     */
    public function formatResourceRow(array $r): array
    {
        $assignedByName = null;
        if (!empty($r['assigned_first_name']) || !empty($r['assigned_last_name'])) {
            $assignedByName = trim(($r['assigned_first_name'] ?? '') . ' ' . ($r['assigned_last_name'] ?? ''));
        }

        $checkedOutByName = null;
        if (!empty($r['checkout_first_name']) || !empty($r['checkout_last_name'])) {
            $checkedOutByName = trim(($r['checkout_first_name'] ?? '') . ' ' . ($r['checkout_last_name'] ?? ''));
        }

        $returnedToName = null;
        if (!empty($r['returned_first_name']) || !empty($r['returned_last_name'])) {
            $returnedToName = trim(($r['returned_first_name'] ?? '') . ' ' . ($r['returned_last_name'] ?? ''));
        }

        return [
            'id'                       => (int) $r['id'],
            'booking_id'               => (int) $r['booking_id'],
            'equipment_id'             => (int) $r['equipment_id'],
            'equipment_name'           => $r['equipment_name'] ?? null,
            'equipment_code'           => $r['equipment_code'] ?? null,
            'category'                 => $r['category'] ?? null,
            'model_number'             => $r['model_number'] ?? null,
            'serial_number'            => $r['serial_number'] ?? null,
            'equipment_description'    => $r['equipment_description'] ?? null,
            'equipment_catalog_status' => $r['equipment_catalog_status'] ?? null,
            'location_id'              => !empty($r['location_id']) ? (int) $r['location_id'] : null,
            'location_name'            => $r['location_name'] ?? null,
            'default_room_id'          => !empty($r['default_room_id']) ? (int) $r['default_room_id'] : null,
            'default_room_name'        => $r['default_room_name'] ?? null,
            'default_room_code'        => $r['default_room_code'] ?? null,
            'status'                   => $r['status'],
            'assigned_by'              => !empty($r['assigned_by']) ? (int) $r['assigned_by'] : null,
            'assigned_by_name'         => $assignedByName,
            'checked_out_at'           => $r['checked_out_at'] ?? null,
            'checked_out_by'           => !empty($r['checked_out_by']) ? (int) $r['checked_out_by'] : null,
            'checked_out_by_name'      => $checkedOutByName,
            'returned_at'              => $r['returned_at'] ?? null,
            'returned_to'              => !empty($r['returned_to']) ? (int) $r['returned_to'] : null,
            'returned_to_name'         => $returnedToName,
            'notes'                    => $r['notes'] ?? null,
            'created_at'               => $r['created_at'] ?? null,
            'updated_at'               => $r['updated_at'] ?? null,
        ];
    }

    /**
     * Check if the equipment is already reserved/in-use for another overlapping active booking.
     *
     * Overlap condition:
     * existing_start < requested_end AND existing_end > requested_start
     *
     * Blocking statuses: reserved, checked_out.
     * Non-blocking statuses: returned, cancelled.
     * Non-blocking bookings: cancelled, rejected.
     *
     * @param int $equipmentId
     * @param string $startTime Y-m-d H:i:s
     * @param string $endTime Y-m-d H:i:s
     * @param int|null $excludeBookingId
     * @param int|null $excludeResourceId
     * @return array{conflict: bool, conflicting_booking: ?array, message: ?string}
     */
    public function checkConflict(
        int $equipmentId,
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null,
        ?int $excludeResourceId = null
    ): array {
        $builder = $this->db->table('booking_resources br');
        $builder->select('br.id, br.booking_id, br.equipment_id, br.status, b.title, b.start_time, b.end_time, b.status as booking_status')
            ->join('bookings b', 'b.id = br.booking_id', 'inner')
            ->where('br.equipment_id', $equipmentId)
            ->whereIn('br.status', ['reserved', 'checked_out'])
            ->whereNotIn('b.status', ['cancelled', 'rejected'])
            ->where('b.start_time <', $endTime)
            ->where('b.end_time >', $startTime);

        if ($excludeBookingId !== null) {
            $builder->where('br.booking_id !=', $excludeBookingId);
        }
        if ($excludeResourceId !== null) {
            $builder->where('br.id !=', $excludeResourceId);
        }

        $row = $builder->orderBy('b.start_time', 'ASC')->get()->getRowArray();

        if ($row !== null) {
            return [
                'conflict'            => true,
                'conflicting_booking' => [
                    'resource_id'     => (int) $row['id'],
                    'booking_id'      => (int) $row['booking_id'],
                    'booking_title'   => $row['title'],
                    'booking_status'  => $row['booking_status'],
                    'start_time'      => $row['start_time'],
                    'end_time'        => $row['end_time'],
                    'resource_status' => $row['status'],
                ],
                'message' => sprintf(
                    "Equipment is already %s for booking '%s' (ID %d) from %s to %s.",
                    $row['status'],
                    $row['title'],
                    (int) $row['booking_id'],
                    $row['start_time'],
                    $row['end_time']
                ),
            ];
        }

        return [
            'conflict'            => false,
            'conflicting_booking' => null,
            'message'             => null,
        ];
    }
}

