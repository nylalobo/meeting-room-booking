<?php

namespace App\Controllers;

use App\Models\Booking as BookingModel;
use App\Models\Facility as FacilityModel;
use App\Models\Location as LocationModel;
use App\Models\Room as RoomModel;
use CodeIgniter\HTTP\ResponseInterface;

class Room extends BaseController
{
    protected RoomModel $roomModel;
    protected BookingModel $bookingModel;
    protected LocationModel $locationModel;
    protected FacilityModel $facilityModel;

    public function __construct()
    {
        $this->roomModel     = new RoomModel();
        $this->bookingModel  = new BookingModel();
        $this->locationModel = new LocationModel();
        $this->facilityModel = new FacilityModel();
    }

    public function index(): ResponseInterface
    {
        $rooms = $this->roomModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $rooms,
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $room = $this->roomModel->getRoomWithDetails($id);

        if ($room === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $room,
        ]);
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (!$this->roomModel->insert($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->roomModel->errors(),
                ]);
        }

        $room = $this->roomModel->find(
            $this->roomModel->getInsertID()
        );

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => 'success',
                'message' => 'Room created successfully.',
                'data'    => $room,
            ]);
    }

    public function update(int $id): ResponseInterface
    {
        $room = $this->roomModel->find($id);

        if ($room === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room not found.',
                ]);
        }

        $data = $this->request->getJSON(true);

        if (!$this->roomModel->update($id, $data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->roomModel->errors(),
                ]);
        }

        $updatedRoom = $this->roomModel->find($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Room updated successfully.',
            'data'    => $updatedRoom,
        ]);
    }

    public function delete(int $id): ResponseInterface
    {
        $room = $this->roomModel->find($id);

        if ($room === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room not found.',
                ]);
        }

        if (!$this->roomModel->delete($id)) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to delete room.',
                ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Room deleted successfully.',
        ]);
    }

    /**
     * Search room availability based on date/time, capacity, location, and facilities.
     * Also provides intelligent alternative room suggestions when a requested room is occupied.
     *
     * GET or POST /api/rooms/availability
     */
    public function availability(): ResponseInterface
    {
        $input = $this->request->is('json')
            ? ($this->request->getJSON(true) ?? [])
            : array_merge($this->request->getGet() ?? [], $this->request->getPost() ?? []);

        $startTimeRaw = trim((string) ($input['start_time'] ?? ''));
        $endTimeRaw   = trim((string) ($input['end_time'] ?? ''));

        $errors = [];
        if ($startTimeRaw === '') {
            $errors['start_time'] = 'start_time is required.';
        }
        if ($endTimeRaw === '') {
            $errors['end_time'] = 'end_time is required.';
        }

        if (!empty($errors)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => implode(' ', $errors),
                    'errors'  => $errors,
                ]);
        }

        $startTimeStamp = strtotime($startTimeRaw);
        $endTimeStamp   = strtotime($endTimeRaw);

        if ($startTimeStamp === false) {
            $errors['start_time'] = 'Invalid start_time format.';
        }
        if ($endTimeStamp === false) {
            $errors['end_time'] = 'Invalid end_time format.';
        }

        if (!empty($errors)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => implode(' ', $errors),
                    'errors'  => $errors,
                ]);
        }

        if ($endTimeStamp <= $startTimeStamp) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'End time must be after start time.',
                    'errors'  => [
                        'end_time' => 'End time must be after start time.',
                    ],
                ]);
        }

        $startTime = date('Y-m-d H:i:s', $startTimeStamp);
        $endTime   = date('Y-m-d H:i:s', $endTimeStamp);

        $locationId       = !empty($input['location_id']) ? (int) $input['location_id'] : null;
        $minCapacity      = !empty($input['capacity'])
            ? (int) $input['capacity']
            : (!empty($input['min_capacity']) ? (int) $input['min_capacity'] : null);
        $specificRoomId   = !empty($input['room_id']) ? (int) $input['room_id'] : null;
        $excludeBookingId = !empty($input['exclude_booking_id']) ? (int) $input['exclude_booking_id'] : null;
        $roomType         = !empty($input['room_type']) ? trim((string) $input['room_type']) : null;

        $requestedFacilities = [];
        if (!empty($input['facilities'])) {
            if (is_array($input['facilities'])) {
                $requestedFacilities = array_map('intval', $input['facilities']);
            } elseif (is_string($input['facilities'])) {
                $parts = explode(',', $input['facilities']);
                $requestedFacilities = array_filter(array_map('intval', array_map('trim', $parts)));
            }
        }

        // Check specific room existence if requested
        $requestedRoomEntity = null;
        if ($specificRoomId !== null) {
            $requestedRoomEntity = $this->roomModel->getRoomWithDetails($specificRoomId);
            if ($requestedRoomEntity === null) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Room not found.',
                    ]);
            }
        }

        // Retrieve conflicting bookings in interval [startTime, endTime]
        // Conflicts exist only if status is pending or approved
        $conflictBuilder = $this->bookingModel->builder();
        $conflictBuilder->select('id, room_id, user_id, title, start_time, end_time, status')
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_time <', $endTime)
            ->where('end_time >', $startTime);

        if ($excludeBookingId !== null) {
            $conflictBuilder->where('id !=', $excludeBookingId);
        }

        $conflictRows = $conflictBuilder->get()->getResultArray();
        $conflictsByRoom = [];
        foreach ($conflictRows as $c) {
            $rId = (int) $c['room_id'];
            if (!isset($conflictsByRoom[$rId])) {
                $conflictsByRoom[$rId] = [];
            }
            $conflictsByRoom[$rId][] = [
                'booking_id' => (int) $c['id'],
                'title'      => $c['title'],
                'start_time' => $c['start_time'],
                'end_time'   => $c['end_time'],
                'status'     => $c['status'],
            ];
        }

        // Fetch all active rooms with location & facility details
        $allActiveRooms = $this->roomModel->getRoomsWithDetails(null, ['active_only' => 1]);

        foreach ($allActiveRooms as &$r) {
            $rId = (int) $r['id'];
            $hasConflict = !empty($conflictsByRoom[$rId]);
            $r['is_available'] = !$hasConflict;
            $r['conflicts']    = $conflictsByRoom[$rId] ?? [];
        }
        unset($r);

        // Populate requested room status
        $requestedRoomData = null;
        if ($requestedRoomEntity !== null) {
            $reqId = (int) $requestedRoomEntity['id'];
            $requestedRoomData = $requestedRoomEntity;
            $requestedRoomData['is_available'] = empty($conflictsByRoom[$reqId]);
            $requestedRoomData['conflicts']    = $conflictsByRoom[$reqId] ?? [];
        }

        // Filter rooms matching search criteria
        $availableRooms   = [];
        $unavailableRooms = [];

        foreach ($allActiveRooms as $room) {
            $matchesLocation = ($locationId === null || (int) $room['location_id'] === $locationId);
            $matchesCapacity = ($minCapacity === null || (int) $room['capacity'] >= $minCapacity);

            $matchesFacilities = true;
            if (!empty($requestedFacilities)) {
                $roomFacilityIds = array_column($room['facilities'], 'id');
                $diff = array_diff($requestedFacilities, $roomFacilityIds);
                if (!empty($diff)) {
                    $matchesFacilities = false;
                }
            }

            if ($matchesLocation && $matchesCapacity && $matchesFacilities) {
                if ($room['is_available']) {
                    $availableRooms[] = $room;
                } else {
                    $unavailableRooms[] = $room;
                }
            }
        }

        // Calculate Alternative Room Suggestions when a requested room is occupied
        $alternativeSuggestions = [];

        if ($requestedRoomData !== null && !$requestedRoomData['is_available']) {
            $targetLocationId = (int) $requestedRoomData['location_id'];
            $targetCapacity   = (int) $requestedRoomData['capacity'];
            $targetFacilityIds = array_column($requestedRoomData['facilities'], 'id');
            if (!empty($requestedFacilities)) {
                $targetFacilityIds = array_unique(array_merge($targetFacilityIds, $requestedFacilities));
            }

            foreach ($allActiveRooms as $candidate) {
                // Must not be the requested room
                if ((int) $candidate['id'] === (int) $requestedRoomData['id']) {
                    continue;
                }

                // Must be actually available for the requested time window
                if (!$candidate['is_available']) {
                    continue;
                }

                $score = 0;
                $reasons = [];

                // 1. Same Location match (+100 points)
                if ((int) $candidate['location_id'] === $targetLocationId) {
                    $score += 100;
                    $reasons[] = 'Same location (' . ($candidate['location']['name'] ?? 'Primary') . ')';
                } else {
                    $reasons[] = 'Alternative location (' . ($candidate['location']['name'] ?? 'Other') . ')';
                }

                // 2. Sufficient Capacity (+50 points)
                $candCap = (int) $candidate['capacity'];
                if ($candCap >= $targetCapacity) {
                    $score += 50;
                    $reasons[] = "Sufficient capacity ({$candCap} seats >= {$targetCapacity} needed)";
                } elseif ($minCapacity !== null && $candCap >= $minCapacity) {
                    $score += 30;
                    $reasons[] = "Meets minimum requested capacity ({$candCap} seats >= {$minCapacity})";
                } else {
                    $reasons[] = "Lower capacity ({$candCap} seats)";
                }

                // Capacity divergence penalty
                $capDiff = abs($candCap - $targetCapacity);
                $score -= min(30, $capDiff);

                // 3. Matching Facilities (+15 points per matching facility)
                $candFacilityIds = array_column($candidate['facilities'], 'id');
                $matchedFacilities = array_intersect($targetFacilityIds, $candFacilityIds);
                $matchCount = count($matchedFacilities);
                if ($matchCount > 0) {
                    $score += ($matchCount * 15);
                    $reasons[] = "Offers {$matchCount} matching facilities";
                }

                $candidate['match_score']   = $score;
                $candidate['match_reasons'] = $reasons;

                $alternativeSuggestions[] = $candidate;
            }

            // Rank alternatives by score descending, then capacity proximity ascending
            usort($alternativeSuggestions, function ($a, $b) use ($targetCapacity) {
                if ($b['match_score'] !== $a['match_score']) {
                    return $b['match_score'] <=> $a['match_score'];
                }
                $diffA = abs($a['capacity'] - $targetCapacity);
                $diffB = abs($b['capacity'] - $targetCapacity);
                return $diffA <=> $diffB;
            });
        }

        $responseData = [
            'search_criteria' => [
                'start_time'          => $startTime,
                'end_time'            => $endTime,
                'location_id'         => $locationId,
                'capacity'            => $minCapacity,
                'facilities'          => $requestedFacilities,
                'room_type'           => $roomType,
                'room_id'             => $specificRoomId,
                'exclude_booking_id'  => $excludeBookingId,
            ],
            'requested_room'          => $requestedRoomData,
            'available_rooms'         => array_values($availableRooms),
            'unavailable_rooms'       => array_values($unavailableRooms),
            'alternative_suggestions' => array_values($alternativeSuggestions),
            'alternatives'            => array_values($alternativeSuggestions),
            'total_available'         => count($availableRooms),
            'total_unavailable'       => count($unavailableRooms),
        ];

        if ($requestedRoomData !== null) {
            $responseData['is_available'] = (bool) $requestedRoomData['is_available'];
            $responseData['room']         = $requestedRoomData;
            $responseData['conflicts']    = $requestedRoomData['conflicts'] ?? [];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $responseData,
        ]);
    }

    /**
     * Shorthand endpoint to check a specific room's availability.
     *
     * GET /api/rooms/(:num)/availability
     */
    public function roomAvailability(int $id): ResponseInterface
    {
        $getParams = $this->request->getGet() ?? [];
        $getParams['room_id'] = $id;
        $this->request->setGlobal('get', $getParams);

        return $this->availability();
    }
}