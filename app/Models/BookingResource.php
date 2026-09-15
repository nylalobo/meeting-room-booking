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
}

