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

    /**
     * Get rooms enriched with location details and associated facilities.
     *
     * @param int|null $roomId Optional specific room ID
     * @param array $filters Optional filter criteria (location_id, min_capacity, active_only)
     * @return array
     */
    public function getRoomsWithDetails(?int $roomId = null, array $filters = []): array
    {
        $builder = $this->builder();
        $builder->select('rooms.*, locations.name as location_name, locations.city as location_city, locations.address as location_address, locations.country as location_country')
            ->join('locations', 'locations.id = rooms.location_id', 'left');

        if ($roomId !== null) {
            $builder->where('rooms.id', $roomId);
        }

        if (!empty($filters['active_only'])) {
            $builder->where('rooms.is_active', 1);
        }

        if (!empty($filters['location_id'])) {
            $builder->where('rooms.location_id', (int) $filters['location_id']);
        }

        if (!empty($filters['min_capacity'])) {
            $builder->where('rooms.capacity >=', (int) $filters['min_capacity']);
        }

        $rooms = $builder->orderBy('rooms.name', 'ASC')->get()->getResultArray();

        if (empty($rooms)) {
            return [];
        }

        $roomIds = array_column($rooms, 'id');

        // Fetch facilities for these rooms in a single query
        $db = \Config\Database::connect();
        $facilityRows = $db->table('room_facilities')
            ->select('room_facilities.room_id, facilities.id as facility_id, facilities.name as facility_name, facilities.description as facility_description')
            ->join('facilities', 'facilities.id = room_facilities.facility_id')
            ->whereIn('room_facilities.room_id', $roomIds)
            ->where('facilities.is_active', 1)
            ->get()
            ->getResultArray();

        $facilitiesByRoom = [];
        foreach ($facilityRows as $row) {
            $rId = (int) $row['room_id'];
            if (!isset($facilitiesByRoom[$rId])) {
                $facilitiesByRoom[$rId] = [];
            }
            $facilitiesByRoom[$rId][] = [
                'id'          => (int) $row['facility_id'],
                'name'        => $row['facility_name'],
                'description' => $row['facility_description'],
            ];
        }

        foreach ($rooms as &$room) {
            $rId = (int) $room['id'];
            $room['capacity'] = (int) $room['capacity'];
            $room['location_id'] = (int) $room['location_id'];
            $room['is_active'] = (int) $room['is_active'];
            $room['location'] = [
                'id'      => (int) $room['location_id'],
                'name'    => $room['location_name'] ?? 'Unknown Location',
                'city'    => $room['location_city'] ?? null,
                'address' => $room['location_address'] ?? null,
                'country' => $room['location_country'] ?? null,
            ];
            $room['facilities'] = $facilitiesByRoom[$rId] ?? [];
        }
        unset($room);

        return $rooms;
    }

    /**
     * Get a single room enriched with location and facility details.
     */
    public function getRoomWithDetails(int $id): ?array
    {
        $rooms = $this->getRoomsWithDetails($id);
        return !empty($rooms) ? $rooms[0] : null;
    }
}