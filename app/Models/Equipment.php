<?php

namespace App\Models;

use CodeIgniter\Model;

class Equipment extends Model
{
    protected $table            = 'equipment';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'name',
        'code',
        'category',
        'model_number',
        'serial_number',
        'description',
        'location_id',
        'default_room_id',
        'status',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $beforeInsert = ['cleanData'];
    protected $beforeUpdate = ['cleanData'];

    protected $validationRules = [
        'name'            => 'required|min_length[2]|max_length[150]',
        'code'            => 'required|min_length[2]|max_length[50]|is_unique[equipment.code,id,{id}]',
        'category'        => 'required|min_length[2]|max_length[50]',
        'model_number'    => 'permit_empty|max_length[100]',
        'serial_number'   => 'permit_empty|max_length[100]|is_unique[equipment.serial_number,id,{id}]',
        'description'     => 'permit_empty|max_length[2000]',
        'location_id'     => 'permit_empty|is_natural_no_zero',
        'default_room_id' => 'permit_empty|is_natural_no_zero',
        'status'          => 'required|in_list[available,maintenance,damaged,retired]',
        'notes'           => 'permit_empty|max_length[2000]',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'Equipment name is required.',
            'min_length' => 'Equipment name must be at least 2 characters.',
            'max_length' => 'Equipment name cannot exceed 150 characters.',
        ],
        'code' => [
            'required'   => 'Equipment code is required.',
            'min_length' => 'Equipment code must be at least 2 characters.',
            'max_length' => 'Equipment code cannot exceed 50 characters.',
            'is_unique'  => 'Equipment code must be unique. This code is already in use.',
        ],
        'category' => [
            'required'   => 'Equipment category is required.',
            'min_length' => 'Equipment category must be at least 2 characters.',
            'max_length' => 'Equipment category cannot exceed 50 characters.',
        ],
        'model_number' => [
            'max_length' => 'Model number cannot exceed 100 characters.',
        ],
        'serial_number' => [
            'max_length' => 'Serial number cannot exceed 100 characters.',
            'is_unique'  => 'Serial number must be unique. This serial number is already registered.',
        ],
        'location_id' => [
            'is_natural_no_zero' => 'Location ID must be a valid positive integer.',
        ],
        'default_room_id' => [
            'is_natural_no_zero' => 'Default room ID must be a valid positive integer.',
        ],
        'status' => [
            'required' => 'Equipment status is required.',
            'in_list'  => 'Status must be one of: available, maintenance, damaged, retired.',
        ],
    ];

    /**
     * Clean and normalize data before inserting/updating.
     */
    protected function cleanData(array $data): array
    {
        if (isset($data['data']['name'])) {
            $data['data']['name'] = trim(strip_tags((string) $data['data']['name']));
        }
        if (isset($data['data']['code'])) {
            $data['data']['code'] = trim(strip_tags((string) $data['data']['code']));
        }
        if (isset($data['data']['category'])) {
            $data['data']['category'] = strtolower(trim(strip_tags((string) $data['data']['category'])));
        }
        if (isset($data['data']['model_number'])) {
            $val = trim(strip_tags((string) $data['data']['model_number']));
            $data['data']['model_number'] = $val !== '' ? $val : null;
        }
        if (isset($data['data']['serial_number'])) {
            $val = trim(strip_tags((string) $data['data']['serial_number']));
            $data['data']['serial_number'] = $val !== '' ? $val : null;
        }
        if (isset($data['data']['description'])) {
            $val = trim((string) $data['data']['description']);
            $data['data']['description'] = $val !== '' ? $val : null;
        }
        if (isset($data['data']['location_id'])) {
            $val = $data['data']['location_id'];
            $data['data']['location_id'] = (!empty($val) && (int) $val > 0) ? (int) $val : null;
        }
        if (isset($data['data']['default_room_id'])) {
            $val = $data['data']['default_room_id'];
            $data['data']['default_room_id'] = (!empty($val) && (int) $val > 0) ? (int) $val : null;
        }
        if (isset($data['data']['status'])) {
            $data['data']['status'] = strtolower(trim((string) $data['data']['status']));
        }
        if (isset($data['data']['notes'])) {
            $val = trim((string) $data['data']['notes']);
            $data['data']['notes'] = $val !== '' ? $val : null;
        }

        return $data;
    }
}

