<?php

namespace App\Controllers;

use App\Models\Booking as BookingModel;
use App\Models\BookingCheckin as BookingCheckinModel;
use App\Models\BookingParticipant as BookingParticipantModel;
use App\Models\Facility as FacilityModel;
use App\Models\Location as LocationModel;
use App\Models\Room as RoomModel;
use App\Models\User as UserModel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use CodeIgniter\HTTP\ResponseInterface;

class Room extends BaseController
{
    protected RoomModel $roomModel;
    protected BookingModel $bookingModel;
    protected BookingCheckinModel $bookingCheckinModel;
    protected BookingParticipantModel $bookingParticipantModel;
    protected UserModel $userModel;
    protected LocationModel $locationModel;
    protected FacilityModel $facilityModel;

    public function __construct()
    {
        $this->roomModel               = new RoomModel();
        $this->bookingModel            = new BookingModel();
        $this->bookingCheckinModel     = new BookingCheckinModel();
        $this->bookingParticipantModel = new BookingParticipantModel();
        $this->userModel               = new UserModel();
        $this->locationModel           = new LocationModel();
        $this->facilityModel           = new FacilityModel();
    }

    public function index(): ResponseInterface
    {
        if ($this->request->getGet('format') === 'select2') {
            return $this->select2();
        }

        $all = $this->request->getGet('all') === '1' || $this->request->getGet('all') === 'true' || $this->request->getGet('paginate') === '0';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 10)));
        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');
        $locationId = $this->request->getGet('location_id');

        $builder = $this->roomModel->builder();

        if ($search !== '') {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('room_code', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $builder->where('is_active', (int) $status);
        }

        if ($locationId !== null && $locationId !== '') {
            $builder->where('location_id', (int) $locationId);
        }

        $total = (clone $builder)->countAllResults();

        if ($all) {
            $rooms = $builder->orderBy('id', 'ASC')->get()->getResultArray();
            return $this->response->setJSON([
                'status'      => 'success',
                'data'        => $rooms,
                'page'        => 1,
                'per_page'    => $total > 0 ? $total : 1,
                'total'       => $total,
                'total_pages' => 1,
            ]);
        }

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $rooms = $builder->orderBy('id', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'      => 'success',
            'data'        => $rooms,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
        ]);
    }


    /**
     * Select2 AJAX data source for rooms.
     * Supports search across room name, room code, and location name.
     */
    public function select2(): ResponseInterface
    {
        $session = service('session');
        if (!$session->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'     => 'error',
                'message'    => 'Unauthorized. Please log in.',
                'results'    => [],
                'pagination' => ['more' => false],
            ]);
        }

        $id = $this->request->getGet('id');
        if ($id !== null && is_numeric($id) && (int) $id > 0) {
            $room = $this->roomModel->builder()
                ->select('rooms.id, rooms.name, rooms.room_code, rooms.capacity, rooms.location_id, rooms.is_active, locations.name as location_name')
                ->join('locations', 'locations.id = rooms.location_id', 'left')
                ->where('rooms.id', (int) $id)
                ->get()
                ->getRowArray();

            if ($room) {
                $code = !empty($room['room_code']) ? " ({$room['room_code']})" : '';
                $loc = !empty($room['location_name']) ? " — {$room['location_name']}" : '';
                $text = "{$room['name']}{$code}{$loc}";
                return $this->response->setJSON([
                    'results' => [[
                        'id'            => (int) $room['id'],
                        'text'          => $text,
                        'name'          => $room['name'],
                        'room_code'     => $room['room_code'] ?? '',
                        'location_name' => $room['location_name'] ?? '',
                        'capacity'      => (int) ($room['capacity'] ?? 0),
                    ]],
                    'pagination' => ['more' => false],
                ]);
            }
            return $this->response->setJSON([
                'results'    => [],
                'pagination' => ['more' => false],
            ]);
        }

        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q') ?? ''));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(50, max(5, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 20)));
        $includeId = $this->request->getGet('include_id');
        $includeIdInt = ($includeId !== null && is_numeric($includeId)) ? (int) $includeId : null;

        $builder = $this->roomModel->builder();
        $builder->select('rooms.id, rooms.name, rooms.room_code, rooms.capacity, rooms.location_id, rooms.is_active, locations.name as location_name');
        $builder->join('locations', 'locations.id = rooms.location_id', 'left');

        if ($includeIdInt !== null) {
            $builder->groupStart()
                ->where('rooms.is_active', 1)
                ->orWhere('rooms.id', $includeIdInt)
                ->groupEnd();
        } else {
            $builder->where('rooms.is_active', 1);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('rooms.name', $search)
                ->orLike('rooms.room_code', $search)
                ->orLike('locations.name', $search)
                ->groupEnd();
        }

        $totalCount = (clone $builder)->countAllResults();

        $offset = ($page - 1) * $perPage;
        $rooms = $builder->orderBy('rooms.name', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $results = [];
        foreach ($rooms as $r) {
            $code = !empty($r['room_code']) ? " ({$r['room_code']})" : '';
            $loc = !empty($r['location_name']) ? " — {$r['location_name']}" : '';
            $text = "{$r['name']}{$code}{$loc}";
            $results[] = [
                'id'            => (int) $r['id'],
                'text'          => $text,
                'name'          => $r['name'],
                'room_code'     => $r['room_code'] ?? '',
                'location_name' => $r['location_name'] ?? '',
                'capacity'      => (int) ($r['capacity'] ?? 0),
            ];
        }

        $hasMore = ($offset + count($results)) < $totalCount;

        return $this->response->setJSON([
            'results'    => $results,
            'pagination' => [
                'more' => $hasMore,
            ],
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

    /**
     * RBAC helper matching Equipment approach.
     */
    protected function canManageRooms(): bool
    {
        $session = service('session');
        $userId = (int) ($session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $currentUserRoleName = (string) ($session->get('role_name') ?? $session->get('role') ?? '');
        $currentUserRoleId   = (int) ($session->get('role_id') ?? 0);

        if (empty($currentUserRoleName) || $currentUserRoleId <= 0) {
            $db = \Config\Database::connect();
            $user = $db->table('users')
                       ->select('users.id, users.role_id, roles.name as role_name')
                       ->join('roles', 'roles.id = users.role_id', 'left')
                       ->where('users.id', $userId)
                       ->get()
                       ->getRowArray();
            if ($user) {
                $currentUserRoleId   = (int) ($user['role_id'] ?? 0);
                $currentUserRoleName = (string) ($user['role_name'] ?? '');
            }
        }

        if ($currentUserRoleId === 1 || $currentUserRoleId === 6) {
            return true;
        }

        return in_array($currentUserRoleName, ['Admin', 'Facilities Manager', 'Facility Manager'], true);
    }

    public function create(): ResponseInterface
    {
        if (!$this->canManageRooms()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage rooms.',
            ]);
        }

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
        if (!$this->canManageRooms()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage rooms.',
            ]);
        }

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
        if (!$this->canManageRooms()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage rooms.',
            ]);
        }

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

    /**
     * Get the current approved booking in this room that is currently eligible for check-in.
     *
     * GET /api/rooms/(:num)/current-booking
     */
    public function currentBooking(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $room = $this->roomModel->find($id);
        if ($room === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Room not found.',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $booking = $this->bookingModel->getCurrentEligibleBookingForRoom($id, $now);

        if ($booking === null) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'No booking is currently eligible for check-in in this room.',
                'data'    => null,
            ]);
        }

        // Resolve current user role
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');
        $currentUserRecord = $this->userModel->find($currentUserId);
        if (empty($currentUserRoleName) && !empty($currentUserRecord['role_id'])) {
            $db = \Config\Database::connect();
            $roleRow = $db->table('roles')->where('id', $currentUserRecord['role_id'])->get()->getRowArray();
            if ($roleRow) {
                $currentUserRoleName = $roleRow['name'];
            }
        }

        $isOrganizer = ((int) $booking['user_id'] === $currentUserId);
        $isElevatedRole = in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true);

        $participant = $this->bookingParticipantModel
            ->where('booking_id', (int) $booking['id'])
            ->where('user_id', $currentUserId)
            ->first();
        $isParticipant = ($participant !== null && $participant['response_status'] !== 'declined');

        $isAuthorized = ($isOrganizer || $isElevatedRole || $isParticipant);

        // Current user check-in status
        $userCheckin = $this->bookingCheckinModel->getLatestCheckinForUser((int) $booking['id'], $currentUserId);
        $checkInStatus = $userCheckin ? $userCheckin['status'] : 'not_checked_in';
        $checkInTime   = $userCheckin ? $userCheckin['check_in_time'] : null;
        $checkOutTime  = $userCheckin ? $userCheckin['check_out_time'] : null;
        $checkInMethod = $userCheckin ? $userCheckin['check_in_method'] : null;
        $checkinId     = $userCheckin ? (int) $userCheckin['id'] : null;

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'id'                 => (int) $booking['id'],
                'booking_id'         => (int) $booking['id'],
                'title'              => $booking['title'],
                'description'        => $booking['description'] ?? null,
                'room_id'            => (int) $booking['room_id'],
                'room_name'          => $booking['room_name'] ?? $room['name'],
                'room_code'          => $booking['room_code'] ?? $room['room_code'],
                'start_time'         => $booking['start_time'],
                'end_time'           => $booking['end_time'],
                'status'             => $booking['status'],
                'recurring_group_id' => $booking['recurring_group_id'] ?? null,
                'is_recurring'       => !empty($booking['recurring_group_id']),
                'organizer'          => [
                    'id'              => (int) $booking['user_id'],
                    'first_name'      => $booking['first_name'] ?? '',
                    'last_name'       => $booking['last_name'] ?? '',
                    'email'           => $booking['email'] ?? '',
                    'department_id'   => !empty($booking['department_id']) ? (int) $booking['department_id'] : null,
                    'department_name' => $booking['department_name'] ?? null,
                ],
                'is_authorized'      => $isAuthorized,
                'check_in_status'    => $checkInStatus,
                'check_in_time'      => $checkInTime,
                'check_out_time'     => $checkOutTime,
                'check_in_method'    => $checkInMethod,
                'checkin_id'         => $checkinId,
            ],
        ]);
    }

    /**
     * Get room QR code identity, check-in URL, and rendered vector SVG QR code.
     *
     * GET /api/rooms/(:num)/qr-code
     */
    public function qrCode(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $room = $this->roomModel->find($id);
        if ($room === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Room not found.',
            ]);
        }

        if (isset($room['is_active']) && (int) $room['is_active'] !== 1) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot generate QR code for an inactive room.',
            ]);
        }

        $roomCode = trim((string) ($room['room_code'] ?? ''));
        if ($roomCode === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Room has no assigned room code.',
            ]);
        }

        $checkInPath = '/check-in/room/' . rawurlencode($roomCode);
        $checkInUrl  = rtrim(base_url(), '/') . $checkInPath;

        // Render vector SVG QR code using chillerlan/php-qrcode
        $options = new QROptions([
            'outputBase64'     => false,
            'svgAddXmlHeader'  => false,
            'scale'            => 6,
            'drawLightModules' => true,
        ]);
        $qr = new QRCode($options);
        $svgMarkup = $qr->render($checkInUrl);

        $base64Options = new QROptions([
            'outputBase64'     => true,
            'svgAddXmlHeader'  => true,
            'scale'            => 6,
            'drawLightModules' => true,
        ]);
        $dataUri = (new QRCode($base64Options))->render($checkInUrl);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'room_id'       => (int) $room['id'],
                'room_name'     => (string) $room['name'],
                'room_code'     => $roomCode,
                'check_in_path' => $checkInPath,
                'check_in_url'  => $checkInUrl,
                'qr_payload'    => $checkInUrl,
                'qr_svg'        => $svgMarkup,
                'qr_data_uri'   => $dataUri,
            ],
        ]);
    }

    /**
     * Mobile-first QR check-in landing page.
     *
     * GET /check-in/room/(:segment)
     */
    public function checkInLanding(string $roomCode): string|ResponseInterface
    {
        $rawCode = rawurldecode(trim($roomCode));

        if ($rawCode === '') {
            $this->response->setStatusCode(404);
            return view('checkin/room', [
                'title'        => 'Room Not Found',
                'state'        => 'room_not_found',
                'room'         => null,
                'roomCode'     => '',
                'booking'      => null,
                'user'         => null,
                'isLoggedIn'   => false,
                'isAuthorized' => false,
                'checkInStatus'=> 'not_found',
                'loginUrl'     => base_url('login'),
                'safeReturnUrl'=> '/',
            ]);
        }

        // Room lookup by room_code
        $room = $this->roomModel->where('room_code', $rawCode)->first();

        if ($room === null) {
            $this->response->setStatusCode(404);
            return view('checkin/room', [
                'title'        => 'Room Not Found',
                'state'        => 'room_not_found',
                'room'         => null,
                'roomCode'     => $rawCode,
                'booking'      => null,
                'user'         => null,
                'isLoggedIn'   => false,
                'isAuthorized' => false,
                'checkInStatus'=> 'not_found',
                'loginUrl'     => base_url('login'),
                'safeReturnUrl'=> '/',
            ]);
        }

        // Check if room is active
        $isActive = isset($room['is_active']) ? (int) $room['is_active'] === 1 : true;
        if (!$isActive) {
            $this->response->setStatusCode(422);
            return view('checkin/room', [
                'title'        => 'Room Unavailable',
                'state'        => 'room_inactive',
                'room'         => [
                    'id'        => (int) $room['id'],
                    'name'      => (string) $room['name'],
                    'room_code' => (string) $room['room_code'],
                ],
                'roomCode'     => $rawCode,
                'booking'      => null,
                'user'         => null,
                'isLoggedIn'   => false,
                'isAuthorized' => false,
                'checkInStatus'=> 'inactive',
                'loginUrl'     => base_url('login'),
                'safeReturnUrl'=> '/',
            ]);
        }

        // Resolve location name
        $locationName = 'Main Building';
        if (!empty($room['location_id'])) {
            $loc = $this->locationModel->find($room['location_id']);
            if ($loc && !empty($loc['name'])) {
                $locationName = $loc['name'];
            }
        }

        $roomData = [
            'id'            => (int) $room['id'],
            'name'          => (string) $room['name'],
            'room_code'     => (string) $room['room_code'],
            'capacity'      => !empty($room['capacity']) ? (int) $room['capacity'] : null,
            'floor'         => !empty($room['floor']) ? (string) $room['floor'] : null,
            'description'   => !empty($room['description']) ? (string) $room['description'] : null,
            'location_name' => $locationName,
        ];

        // Resolve user session
        $session = service('session');
        $isLoggedIn = $session->get('isLoggedIn') === true && !empty($session->get('user_id'));
        $currentUserId = $isLoggedIn ? (int) $session->get('user_id') : null;

        $userData = null;
        $currentUserRoleName = '';
        if ($isLoggedIn && $currentUserId) {
            $currentUserRoleName = (string) ($session->get('role_name') ?? '');
            $userRecord = $this->userModel->find($currentUserId);
            if (empty($currentUserRoleName) && !empty($userRecord['role_id'])) {
                $db = \Config\Database::connect();
                $roleRow = $db->table('roles')->where('id', $userRecord['role_id'])->get()->getRowArray();
                if ($roleRow) {
                    $currentUserRoleName = $roleRow['name'];
                }
            }

            $userData = [
                'id'         => $currentUserId,
                'first_name' => $session->get('first_name') ?? ($userRecord['first_name'] ?? ''),
                'last_name'  => $session->get('last_name') ?? ($userRecord['last_name'] ?? ''),
                'email'      => $session->get('email') ?? ($userRecord['email'] ?? ''),
                'role_name'  => $currentUserRoleName ?: 'Member',
            ];
        }

        // Resolve current eligible booking
        $now = date('Y-m-d H:i:s');
        $booking = $this->bookingModel->getCurrentEligibleBookingForRoom((int) $room['id'], $now);

        $safeReturnUrl = '/check-in/room/' . rawurlencode($room['room_code']);
        $loginUrl = base_url('login?return=' . rawurlencode($safeReturnUrl));

        if ($booking === null) {
            return view('checkin/room', [
                'title'        => $room['name'] . ' - Check In',
                'state'        => 'no_eligible_booking',
                'room'         => $roomData,
                'roomCode'     => (string) $room['room_code'],
                'booking'      => null,
                'user'         => $userData,
                'isLoggedIn'   => $isLoggedIn,
                'isAuthorized' => false,
                'roleLabel'    => '',
                'checkInStatus'=> 'no_meeting',
                'userCheckin'  => null,
                'loginUrl'     => $loginUrl,
                'safeReturnUrl'=> $safeReturnUrl,
            ]);
        }

        // Format booking data
        $organizerName = 'Organizer';
        if (!empty($booking['first_name']) || !empty($booking['last_name'])) {
            $organizerName = trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? ''));
        }

        $bookingData = [
            'id'                 => (int) $booking['id'],
            'title'              => (string) $booking['title'],
            'description'        => $booking['description'] ?? null,
            'start_time'         => (string) $booking['start_time'],
            'end_time'           => (string) $booking['end_time'],
            'status'             => (string) $booking['status'],
            'organizer_id'       => (int) $booking['user_id'],
            'organizer_name'     => $organizerName,
            'organizer_email'    => $booking['email'] ?? null,
            'organizer_dept'     => $booking['department_name'] ?? null,
            'is_recurring'       => !empty($booking['recurring_group_id']),
        ];

        // Determine authorization and check-in status
        $isAuthorized = false;
        $checkInStatus = 'login_required';
        $userCheckin = null;
        $roleLabel = '';

        if ($isLoggedIn && $currentUserId) {
            $isOrganizer = ((int) $booking['user_id'] === $currentUserId);
            $isElevatedRole = in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true);

            $participant = $this->bookingParticipantModel
                ->where('booking_id', (int) $booking['id'])
                ->where('user_id', $currentUserId)
                ->first();
            $isParticipant = ($participant !== null && $participant['response_status'] !== 'declined');

            $isAuthorized = ($isOrganizer || $isElevatedRole || $isParticipant);

            if ($isOrganizer) {
                $roleLabel = 'Organizer';
            } elseif ($isParticipant) {
                $roleLabel = 'Attendee';
            } elseif ($isElevatedRole) {
                $roleLabel = $currentUserRoleName;
            }

            // Check active check-in record
            $userCheckin = $this->bookingCheckinModel->getLatestCheckinForUser((int) $booking['id'], $currentUserId);
            $checkInStatus = $userCheckin ? $userCheckin['status'] : 'not_checked_in';
        }

        return view('checkin/room', [
            'title'         => $room['name'] . ' - Check In',
            'state'         => 'eligible_booking',
            'room'          => $roomData,
            'roomCode'      => (string) $room['room_code'],
            'booking'       => $bookingData,
            'user'          => $userData,
            'isLoggedIn'    => $isLoggedIn,
            'isAuthorized'  => $isAuthorized,
            'roleLabel'     => $roleLabel,
            'checkInStatus' => $checkInStatus,
            'userCheckin'   => $userCheckin,
            'loginUrl'      => $loginUrl,
            'safeReturnUrl' => $safeReturnUrl,
        ]);
    }
}
