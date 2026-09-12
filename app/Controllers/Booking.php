<?php

namespace App\Controllers;

use App\Models\AuditLog as AuditLogModel;
use App\Models\Booking as BookingModel;
use App\Models\Department as DepartmentModel;
use App\Models\Room as RoomModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Booking extends BaseController
{
    protected BookingModel $bookingModel;
    protected RoomModel $roomModel;
    protected UserModel $userModel;
    protected DepartmentModel $departmentModel;
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->bookingModel    = new BookingModel();
        $this->roomModel       = new RoomModel();
        $this->userModel       = new UserModel();
        $this->departmentModel = new DepartmentModel();
        $this->auditLogModel   = new AuditLogModel();
    }

    /**
     * Get all bookings.
     */
    public function index(): ResponseInterface
    {
        $bookings = $this->bookingModel
            ->orderBy('start_time', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $bookings,
        ]);
    }

    /**
     * Get all bookings with room and organizer details.
     *
     * Used by the frontend bookings page.
     */
    public function apiIndex(): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');
        $currentUserRecord = !empty($currentUserId) ? $this->userModel->find($currentUserId) : null;
        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;

        $bookings = $this->bookingModel
            ->orderBy('start_time', 'ASC')
            ->findAll();

        $data = [];

        foreach ($bookings as $booking) {
            $room = $this->roomModel->find($booking['room_id']);
            $user = $this->userModel->find($booking['user_id']);

            $booking['room_name'] = $room['name'] ?? 'Unknown Room';
            $booking['room_code'] = $room['room_code'] ?? null;

            $booking['organizer_name'] = $user
                ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
                : 'Unknown User';

            $booking['organizer_email'] = $user['email'] ?? null;

            if ($user && !empty($user['department_id'])) {
                $dept = $this->departmentModel->find($user['department_id']);
                $booking['organizer_department_name'] = $dept['name'] ?? null;
                $booking['organizer_department_id']   = (int) $user['department_id'];
            } else {
                $booking['organizer_department_name'] = null;
                $booking['organizer_department_id']   = null;
            }

            if (!empty($booking['approver_id'])) {
                $approver = $this->userModel->find($booking['approver_id']);
                $booking['approver_name'] = $approver
                    ? trim(($approver['first_name'] ?? '') . ' ' . ($approver['last_name'] ?? ''))
                    : null;
                $booking['approver_email'] = $approver['email'] ?? null;
            } else {
                $booking['approver_name'] = null;
                $booking['approver_email'] = null;
            }

            $booking['can_approve'] = $this->canUserApproveBooking(
                $currentUserId,
                $currentUserRoleName,
                $currentUserDeptId,
                $booking,
                $user
            );

            $data[] = $booking;
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Get a single booking.
     */
    public function show(int $id): ResponseInterface
    {
        $booking = $this->bookingModel->find($id);

        if ($booking === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $booking,
        ]);
    }

    /**
     * Preview recurrence dates and check conflicts without creating records.
     *
     * POST /api/bookings/recurring-preview
     */
    public function recurringPreview(): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];

        $roomId = (int) ($data['room_id'] ?? 0);
        $startTime = trim((string) ($data['start_time'] ?? ''));
        $endTime = trim((string) ($data['end_time'] ?? ''));
        $recurrence = is_array($data['recurrence'] ?? null) ? $data['recurrence'] : [];
        $excludeBookingId = !empty($data['exclude_booking_id']) ? (int) $data['exclude_booking_id'] : null;

        $errors = [];
        if ($roomId <= 0) {
            $errors['room_id'] = 'Room is required.';
        }
        if ($startTime === '') {
            $errors['start_time'] = 'Start time is required.';
        }
        if ($endTime === '') {
            $errors['end_time'] = 'End time is required.';
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $errors,
            ]);
        }

        if (strtotime($endTime) <= strtotime($startTime)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => [
                    'end_time' => 'End time must be after start time.',
                ],
            ]);
        }

        $room = $this->roomModel->find($roomId);
        if ($room === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Room not found.',
            ]);
        }

        $validation = $this->bookingModel->validateRecurrenceConfig($startTime, $endTime, $recurrence);
        if (!$validation['valid']) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation['errors'],
            ]);
        }

        $occurrences = $this->bookingModel->generateOccurrences($startTime, $endTime, $validation['cleaned']);
        if (empty($occurrences)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => [
                    'recurrence' => 'No occurrences could be generated for the specified pattern.',
                ],
            ]);
        }

        if ($this->bookingModel->hasSelfOverlap($occurrences)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => [
                    'recurrence' => 'Occurrences within the recurring series overlap each other.',
                ],
            ]);
        }

        $conflictCheck = $this->bookingModel->checkOccurrencesConflicts($roomId, $occurrences, $excludeBookingId);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'total_occurrences'     => $conflictCheck['total_occurrences'],
                'available_occurrences' => $conflictCheck['available_occurrences'],
                'conflicts_count'       => $conflictCheck['conflicts_count'],
                'occurrences'           => $conflictCheck['occurrences'],
                'conflicts'             => $conflictCheck['conflicts'],
            ],
        ]);
    }

    /**
     * Calendar events feed endpoint.
     *
     * GET /api/bookings/calendar
     */
    public function calendar(): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $startRaw = trim((string) ($this->request->getGet('start') ?? ''));
        $endRaw   = trim((string) ($this->request->getGet('end') ?? ''));

        $errors = [];
        if ($startRaw === '') {
            $errors['start'] = 'start is required.';
        }
        if ($endRaw === '') {
            $errors['end'] = 'end is required.';
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => implode(' ', $errors),
                'errors'  => $errors,
            ]);
        }

        $startTimeStamp = strtotime($startRaw);
        $endTimeStamp   = strtotime($endRaw);

        if ($startTimeStamp === false) {
            $errors['start'] = 'Invalid start format.';
        }
        if ($endTimeStamp === false) {
            $errors['end'] = 'Invalid end format.';
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => implode(' ', $errors),
                'errors'  => $errors,
            ]);
        }

        if ($endTimeStamp <= $startTimeStamp) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'End time must be after start time.',
                'errors'  => [
                    'end' => 'End time must be after start time.',
                ],
            ]);
        }

        $filters = [];

        $roomId = $this->request->getGet('room_id');
        if ($roomId !== null && $roomId !== '') {
            if (!ctype_digit((string) $roomId) || (int) $roomId <= 0) {
                $errors['room_id'] = 'room_id must be a positive integer.';
            } else {
                $filters['room_id'] = (int) $roomId;
            }
        }

        $locationId = $this->request->getGet('location_id');
        if ($locationId !== null && $locationId !== '') {
            if (!ctype_digit((string) $locationId) || (int) $locationId <= 0) {
                $errors['location_id'] = 'location_id must be a positive integer.';
            } else {
                $filters['location_id'] = (int) $locationId;
            }
        }

        $userId = $this->request->getGet('user_id');
        if ($userId !== null && $userId !== '') {
            if (!ctype_digit((string) $userId) || (int) $userId <= 0) {
                $errors['user_id'] = 'user_id must be a positive integer.';
            } else {
                $filters['user_id'] = (int) $userId;
            }
        }

        $status = $this->request->getGet('status');
        if ($status !== null && $status !== '') {
            $validStatuses = ['pending', 'approved', 'rejected', 'cancelled', 'completed'];
            if (!in_array($status, $validStatuses, true)) {
                $errors['status'] = 'Invalid booking status.';
            } else {
                $filters['status'] = $status;
            }
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => implode(' ', $errors),
                'errors'  => $errors,
            ]);
        }

        $startHasTime = (strpos($startRaw, 'T') !== false || strpos($startRaw, ' ') !== false || strpos($startRaw, ':') !== false);
        $endHasTime   = (strpos($endRaw, 'T') !== false || strpos($endRaw, ' ') !== false || strpos($endRaw, ':') !== false);

        $rangeStart = $startHasTime ? date('Y-m-d H:i:s', $startTimeStamp) : date('Y-m-d 00:00:00', $startTimeStamp);
        $rangeEnd   = $endHasTime ? date('Y-m-d H:i:s', $endTimeStamp) : date('Y-m-d 23:59:59', $endTimeStamp);

        $events = $this->bookingModel->getCalendarFeed($rangeStart, $rangeEnd, $filters);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $events,
        ]);
    }

    /**
     * Inspect all occurrences of a recurring booking series.
     *
     * GET /api/bookings/series/(:segment)
     */
    public function series(string $groupId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $groupId = trim($groupId);

        if (!BookingModel::isValidUuid($groupId)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Invalid recurring group ID format. Expected UUIDv4.',
            ]);
        }

        $rawOccurrences = $this->bookingModel->getSeriesOccurrences($groupId);

        if (empty($rawOccurrences)) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Recurring booking series not found.',
            ]);
        }

        $currentUserRecord = $this->userModel->find($currentUserId);
        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;

        $pattern = $rawOccurrences[0]['recurrence_pattern'] ?? null;
        $totalOccurrences = !empty($rawOccurrences[0]['recurrence_total']) ? (int) $rawOccurrences[0]['recurrence_total'] : count($rawOccurrences);

        $statusCounts = [
            'count_pending'   => 0,
            'count_approved'  => 0,
            'count_rejected'  => 0,
            'count_cancelled' => 0,
            'count_completed' => 0,
        ];

        $occurrences = [];

        foreach ($rawOccurrences as $row) {
            $st = $row['status'] ?? 'pending';
            $countKey = 'count_' . $st;
            if (isset($statusCounts[$countKey])) {
                $statusCounts[$countKey]++;
            }

            $organizer = [
                'id'            => (int) $row['user_id'],
                'first_name'    => $row['user_first_name'],
                'last_name'     => $row['user_last_name'],
                'email'         => $row['user_email'],
                'department_id' => $row['user_department_id'],
            ];

            $canApprove = $this->canUserApproveBooking(
                $currentUserId,
                $currentUserRoleName,
                $currentUserDeptId,
                $row,
                $organizer
            );

            $organizerName = trim(($row['user_first_name'] ?? '') . ' ' . ($row['user_last_name'] ?? ''));
            if ($organizerName === '') {
                $organizerName = 'Unknown User';
            }

            $approverName = null;
            if (!empty($row['approver_id'])) {
                $approverName = trim(($row['approver_first_name'] ?? '') . ' ' . ($row['approver_last_name'] ?? '')) ?: null;
            }

            $occurrences[] = [
                'id'                        => (int) $row['id'],
                'recurrence_index'          => !empty($row['recurrence_index']) ? (int) $row['recurrence_index'] : null,
                'recurrence_total'          => !empty($row['recurrence_total']) ? (int) $row['recurrence_total'] : $totalOccurrences,
                'start_time'                => $row['start_time'],
                'end_time'                  => $row['end_time'],
                'title'                     => $row['title'],
                'description'               => $row['description'],
                'status'                    => $row['status'],
                'room_id'                   => (int) $row['room_id'],
                'room_name'                 => $row['room_name'] ?? 'Unknown Room',
                'room_code'                 => $row['room_code'] ?? null,
                'location_id'               => !empty($row['location_id']) ? (int) $row['location_id'] : null,
                'location_name'             => $row['location_name'] ?? 'Unknown Location',
                'user_id'                   => (int) $row['user_id'],
                'organizer_name'            => $organizerName,
                'organizer_email'           => $row['user_email'] ?? null,
                'organizer_department_name' => $row['user_department_name'] ?? null,
                'organizer_department_id'   => !empty($row['user_department_id']) ? (int) $row['user_department_id'] : null,
                'approver_id'               => !empty($row['approver_id']) ? (int) $row['approver_id'] : null,
                'approver_name'             => $approverName,
                'approver_email'            => $row['approver_email'] ?? null,
                'approved_at'               => $row['approved_at'] ?? null,
                'rejection_reason'          => $row['rejection_reason'] ?? null,
                'can_approve'               => $canApprove,
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => array_merge([
                'recurring_group_id' => $groupId,
                'recurrence_pattern' => $pattern,
                'total_occurrences'  => $totalOccurrences,
            ], $statusCounts, [
                'occurrences'        => $occurrences,
            ]),
        ]);
    }

    /**
     * Create a new booking (single or recurring series).
     */
    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];

        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');

        $isRecurring = !empty($data['is_recurring']) || !empty($data['recurrence']);
        if ($isRecurring) {
            return $this->createRecurringSeries($data, $currentUserId, $currentUserRoleName);
        }

        // Standard requesters always start as 'pending'; only Admin / Facilities Manager may create as directly approved
        if (!in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true) || empty($data['status'])) {
            $data['status'] = 'pending';
        }

        if ($data['status'] === 'approved') {
            $data['approver_id'] = $currentUserId ?: null;
            $data['approved_at'] = date('Y-m-d H:i:s');
        } else {
            $data['approver_id'] = null;
            $data['approved_at'] = null;
        }
        $data['rejection_reason'] = null;

        $validation = service('validation');

        $validation->setRules(
            $this->bookingModel->getValidationRules(),
            $this->bookingModel->getValidationMessages()
        );

        if (!$validation->run($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $validation->getErrors(),
                ]);
        }

        if (strtotime($data['end_time']) <= strtotime($data['start_time'])) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => [
                        'end_time' => 'End time must be after start time.',
                    ],
                ]);
        }

        $room = $this->roomModel->find($data['room_id']);

        if ($room === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room not found.',
                ]);
        }

        $user = $this->userModel->find($data['user_id']);

        if ($user === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'User not found.',
                ]);
        }

        if (
            $this->bookingModel->hasOverlap(
                (int) $data['room_id'],
                $data['start_time'],
                $data['end_time']
            )
        ) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room is already booked during this time.',
                ]);
        }

        if (!$this->bookingModel->insert($data, false)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->bookingModel->errors(),
                ]);
        }

        $newBookingId = (int) $this->bookingModel->getInsertID();

        // Audit logging
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId ?: ((int) $data['user_id']),
            'action'     => 'booking_created',
            'table_name' => 'bookings',
            'record_id'  => $newBookingId,
            'old_values' => null,
            'new_values' => json_encode([
                'room_id'    => $data['room_id'],
                'user_id'    => $data['user_id'],
                'title'      => $data['title'],
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'status'     => $data['status'],
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $booking = $this->bookingModel->find($newBookingId);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => 'success',
                'message' => 'Booking created successfully.',
                'data'    => $booking,
            ]);
    }

    /**
     * Update an existing booking.
     */
    public function update(int $id): ResponseInterface
    {
        $booking = $this->bookingModel->find($id);

        if ($booking === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking not found.',
                ]);
        }

        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');

        $data = $this->request->getJSON(true) ?? [];

        $startTime = $data['start_time'] ?? $booking['start_time'];
        $endTime   = $data['end_time'] ?? $booking['end_time'];
        $roomId    = (int) ($data['room_id'] ?? $booking['room_id']);

        if (strtotime($endTime) <= strtotime($startTime)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => [
                        'end_time' => 'End time must be after start time.',
                    ],
                ]);
        }

        if (array_key_exists('room_id', $data)) {
            $room = $this->roomModel->find($roomId);

            if ($room === null) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Room not found.',
                    ]);
            }
        }

        if (array_key_exists('user_id', $data)) {
            $user = $this->userModel->find((int) $data['user_id']);

            if ($user === null) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'User not found.',
                    ]);
            }
        }

        if (
            $this->bookingModel->hasOverlap(
                $roomId,
                $startTime,
                $endTime,
                $id
            )
        ) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room is already booked during this time.',
                ]);
        }

        // If time or room changed on an approved booking, reset to pending for re-approval
        $timeOrRoomChanged = ($startTime !== $booking['start_time'] || $endTime !== $booking['end_time'] || $roomId !== (int) $booking['room_id']);
        if ($timeOrRoomChanged && $booking['status'] === 'approved' && !in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true)) {
            $data['status']           = 'pending';
            $data['approver_id']      = null;
            $data['approved_at']      = null;
            $data['rejection_reason'] = null;
        }

        // Prevent unauthorized users from updating status to 'approved' via general update
        if (isset($data['status']) && $data['status'] === 'approved' && $booking['status'] !== 'approved') {
            if (!in_array($currentUserRoleName, ['Admin', 'Facilities Manager', 'Manager'], true)) {
                unset($data['status']);
            }
        }

        if (!$this->bookingModel->update($id, $data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->bookingModel->errors(),
                ]);
        }

        $updatedBooking = $this->bookingModel->find($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Booking updated successfully.',
            'data'    => $updatedBooking,
        ]);
    }

    /**
     * Delete an existing booking.
     */
    public function delete(int $id): ResponseInterface
    {
        $booking = $this->bookingModel->find($id);

        if ($booking === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking not found.',
                ]);
        }

        if (!$this->bookingModel->delete($id)) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to delete booking.',
                ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Booking deleted successfully.',
        ]);
    }

    /**
     * Approve a pending booking.
     *
     * POST /api/bookings/{id}/approve
     */
    public function approve(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');

        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($id);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        if ($booking['status'] === 'approved') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Booking is already approved.',
            ]);
        }

        if ($booking['status'] !== 'pending') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Only pending bookings can be approved.',
            ]);
        }

        $organizer = $this->userModel->find($booking['user_id']);
        $currentUserRecord = $this->userModel->find($currentUserId);
        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;

        // Authorization check
        if (!$this->canUserApproveBooking($currentUserId, $currentUserRoleName, $currentUserDeptId, $booking, $organizer)) {
            if ((int) $booking['user_id'] === $currentUserId && $currentUserRoleName !== 'Admin') {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'Requesters cannot approve their own bookings.',
                ]);
            }

            if ($currentUserRoleName === 'Manager') {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'Managers can only approve bookings for their own department.',
                ]);
            }

            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. You do not have permission to approve this booking.',
            ]);
        }

        // Re-check conflict before final confirmation
        if ($this->bookingModel->hasApprovedConflict((int) $booking['room_id'], $booking['start_time'], $booking['end_time'], $id)) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot approve booking: Room is already reserved by another confirmed booking for this time.',
            ]);
        }

        $updateData = [
            'status'           => 'approved',
            'approver_id'      => $currentUserId,
            'approved_at'      => date('Y-m-d H:i:s'),
            'rejection_reason' => null,
        ];

        if (!$this->bookingModel->update($id, $updateData)) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to approve booking.',
            ]);
        }

        // Audit logging
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_approved',
            'table_name' => 'bookings',
            'record_id'  => $id,
            'old_values' => json_encode(['status' => $booking['status']]),
            'new_values' => json_encode([
                'status'      => 'approved',
                'approver_id' => $currentUserId,
                'approved_at' => $updateData['approved_at'],
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $updatedBooking = $this->bookingModel->find($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Booking approved successfully.',
            'data'    => $updatedBooking,
        ]);
    }

    /**
     * Reject a pending booking with a reason.
     *
     * POST /api/bookings/{id}/reject
     */
    public function reject(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');

        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($id);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        if ($booking['status'] === 'rejected') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Booking is already rejected.',
            ]);
        }

        if ($booking['status'] !== 'pending') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Only pending bookings can be rejected.',
            ]);
        }

        $organizer = $this->userModel->find($booking['user_id']);
        $currentUserRecord = $this->userModel->find($currentUserId);
        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;

        // Authorization check
        if (!$this->canUserApproveBooking($currentUserId, $currentUserRoleName, $currentUserDeptId, $booking, $organizer)) {
            if ((int) $booking['user_id'] === $currentUserId && $currentUserRoleName !== 'Admin') {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'Requesters cannot reject their own bookings.',
                ]);
            }

            if ($currentUserRoleName === 'Manager') {
                return $this->response->setStatusCode(403)->setJSON([
                    'status'  => 'error',
                    'message' => 'Managers can only reject bookings for their own department.',
                ]);
            }

            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. You do not have permission to reject this booking.',
            ]);
        }

        // Rejection reason validation
        $raw = $this->request->getJSON(true) ?? $this->request->getPost();
        $reason = trim((string) ($raw['reason'] ?? $raw['rejection_reason'] ?? ''));

        if (mb_strlen($reason) < 3) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'A valid rejection reason is required (minimum 3 characters).',
                'errors'  => [
                    'reason' => 'A valid rejection reason is required (minimum 3 characters).',
                ],
            ]);
        }

        if (mb_strlen($reason) > 500) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Rejection reason cannot exceed 500 characters.',
                'errors'  => [
                    'reason' => 'Rejection reason cannot exceed 500 characters.',
                ],
            ]);
        }

        $updateData = [
            'status'           => 'rejected',
            'approver_id'      => $currentUserId,
            'rejection_reason' => $reason,
            'approved_at'      => null,
        ];

        if (!$this->bookingModel->update($id, $updateData)) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to reject booking.',
            ]);
        }

        // Audit logging
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_rejected',
            'table_name' => 'bookings',
            'record_id'  => $id,
            'old_values' => json_encode(['status' => $booking['status']]),
            'new_values' => json_encode([
                'status'           => 'rejected',
                'approver_id'      => $currentUserId,
                'rejection_reason' => $reason,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $updatedBooking = $this->bookingModel->find($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Booking rejected successfully.',
            'data'    => $updatedBooking,
        ]);
    }

    /**
     * Get bookings awaiting approval by the current user.
     *
     * GET /api/bookings/pending-approvals
     */
    public function pendingApprovals(): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');

        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!in_array($currentUserRoleName, ['Admin', 'Facilities Manager', 'Manager'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. You do not have permission to view pending approvals.',
            ]);
        }

        $currentUserRecord = $this->userModel->find($currentUserId);
        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;

        $query = $this->bookingModel->where('status', 'pending');

        if ($currentUserRoleName === 'Manager') {
            if ($currentUserDeptId === null) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => [],
                ]);
            }

            // Find user IDs in this department
            $deptUsers = $this->userModel
                ->select('id')
                ->where('department_id', $currentUserDeptId)
                ->findAll();

            $deptUserIds = array_column($deptUsers, 'id');
            if (empty($deptUserIds)) {
                return $this->response->setJSON([
                    'status' => 'success',
                    'data'   => [],
                ]);
            }

            $query->whereIn('user_id', $deptUserIds);
        }

        $bookings = $query->orderBy('start_time', 'ASC')->findAll();
        $enriched = [];

        foreach ($bookings as $booking) {
            $room = $this->roomModel->find($booking['room_id']);
            $organizer = $this->userModel->find($booking['user_id']);

            $booking['room_name'] = $room['name'] ?? 'Unknown Room';
            $booking['room_code'] = $room['room_code'] ?? null;
            $booking['organizer_name'] = $organizer
                ? trim(($organizer['first_name'] ?? '') . ' ' . ($organizer['last_name'] ?? ''))
                : 'Unknown User';
            $booking['organizer_email'] = $organizer['email'] ?? null;

            if ($organizer && !empty($organizer['department_id'])) {
                $dept = $this->departmentModel->find($organizer['department_id']);
                $booking['organizer_department_name'] = $dept['name'] ?? null;
                $booking['organizer_department_id']   = (int) $organizer['department_id'];
            } else {
                $booking['organizer_department_name'] = null;
                $booking['organizer_department_id']   = null;
            }

            $booking['can_approve'] = $this->canUserApproveBooking(
                $currentUserId,
                $currentUserRoleName,
                $currentUserDeptId,
                $booking,
                $organizer
            );

            $enriched[] = $booking;
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $enriched,
        ]);
    }

    /**
     * Determine if a user can approve or reject a specific booking.
     */
    protected function canUserApproveBooking(
        int $userId,
        string $userRoleName,
        ?int $userDeptId,
        array $booking,
        ?array $organizer
    ): bool {
        if (($booking['status'] ?? '') !== 'pending') {
            return false;
        }

        // Global approver: Admin
        if ($userRoleName === 'Admin') {
            return true;
        }

        // Requester cannot self-approve
        if ((int) $booking['user_id'] === $userId) {
            return false;
        }

        // Global room resource approver: Facilities Manager
        if ($userRoleName === 'Facilities Manager') {
            return true;
        }

        // Departmental approver: Manager can approve bookings from employees in their department
        if ($userRoleName === 'Manager') {
            $organizerDeptId = !empty($organizer['department_id']) ? (int) $organizer['department_id'] : null;
            if ($userDeptId !== null && $organizerDeptId !== null && $userDeptId === $organizerDeptId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a recurring booking series atomically.
     */
    protected function createRecurringSeries(array $data, int $currentUserId, string $currentUserRoleName): ResponseInterface
    {
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        // Basic presence validation
        $title = trim((string) ($data['title'] ?? ''));
        $roomId = (int) ($data['room_id'] ?? 0);
        $userId = (int) ($data['user_id'] ?? $currentUserId);
        $startTime = trim((string) ($data['start_time'] ?? ''));
        $endTime = trim((string) ($data['end_time'] ?? ''));
        $recurrence = is_array($data['recurrence'] ?? null) ? $data['recurrence'] : [];

        $errors = [];
        if ($title === '') {
            $errors['title'] = 'Booking title is required.';
        } elseif (mb_strlen($title) > 200) {
            $errors['title'] = 'Booking title cannot exceed 200 characters.';
        }
        if ($roomId <= 0) {
            $errors['room_id'] = 'Room is required.';
        }
        if ($userId <= 0) {
            $errors['user_id'] = 'User is required.';
        }
        if ($startTime === '') {
            $errors['start_time'] = 'Start time is required.';
        }
        if ($endTime === '') {
            $errors['end_time'] = 'End time must be after start time.';
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $errors,
            ]);
        }

        if (strtotime($endTime) <= strtotime($startTime)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => [
                    'end_time' => 'End time must be after start time.',
                ],
            ]);
        }

        // Verify room and user exist
        $room = $this->roomModel->find($roomId);
        if ($room === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Room not found.',
            ]);
        }

        $user = $this->userModel->find($userId);
        if ($user === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'User not found.',
            ]);
        }

        // Validate recurrence config
        $validation = $this->bookingModel->validateRecurrenceConfig($startTime, $endTime, $recurrence);
        if (!$validation['valid']) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation['errors'],
            ]);
        }

        // Generate occurrences
        $occurrences = $this->bookingModel->generateOccurrences($startTime, $endTime, $validation['cleaned']);
        if (empty($occurrences)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => [
                    'recurrence' => 'No occurrences could be generated for the specified pattern.',
                ],
            ]);
        }

        // Check self-overlap within series
        if ($this->bookingModel->hasSelfOverlap($occurrences)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => [
                    'recurrence' => 'Occurrences within the recurring series overlap each other.',
                ],
            ]);
        }

        // Validate conflicts across all occurrences (STRICT mode: all or nothing)
        $conflictCheck = $this->bookingModel->checkOccurrencesConflicts($roomId, $occurrences);
        if ($conflictCheck['has_conflicts']) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'          => 'error',
                'message'         => 'One or more recurring occurrences conflict with existing bookings.',
                'conflicts_count' => $conflictCheck['conflicts_count'],
                'conflicts'       => $conflictCheck['conflicts'],
                'data'            => $conflictCheck,
            ]);
        }

        // Determine status and approver
        $status = 'pending';
        $approverId = null;
        $approvedAt = null;

        if (in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true) && ($data['status'] ?? '') === 'approved') {
            $status = 'approved';
            $approverId = $currentUserId ?: null;
            $approvedAt = date('Y-m-d H:i:s');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        $recurringGroupId = BookingModel::generateUuid();
        $recurrencePattern = $validation['cleaned']['frequency'];
        $totalOccurrences = count($occurrences);

        $createdBookings = [];
        $createdIds = [];

        foreach ($occurrences as $occ) {
            $bookingRecord = [
                'room_id'            => $roomId,
                'user_id'            => $userId,
                'recurring_group_id' => $recurringGroupId,
                'recurrence_pattern' => $recurrencePattern,
                'recurrence_index'   => $occ['occurrence_index'],
                'recurrence_total'   => $totalOccurrences,
                'title'              => $title,
                'description'        => $data['description'] ?? null,
                'start_time'         => $occ['start_time'],
                'end_time'           => $occ['end_time'],
                'status'             => $status,
                'approver_id'        => $approverId,
                'approved_at'        => $approvedAt,
                'rejection_reason'   => null,
            ];

            if (!$this->bookingModel->insert($bookingRecord, false)) {
                $db->transRollback();
                return $this->response->setStatusCode(422)->setJSON([
                    'status' => 'error',
                    'errors' => $this->bookingModel->errors(),
                ]);
            }

            $newId = (int) $this->bookingModel->getInsertID();
            $bookingRecord['id'] = $newId;
            $createdBookings[] = $bookingRecord;
            $createdIds[] = $newId;
        }

        // Audit log for recurring series
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId ?: $userId,
            'action'     => 'recurring_booking_created',
            'table_name' => 'bookings',
            'record_id'  => $createdIds[0],
            'old_values' => null,
            'new_values' => json_encode([
                'recurring_group_id' => $recurringGroupId,
                'recurrence_pattern' => $recurrencePattern,
                'total_occurrences'  => $totalOccurrences,
                'room_id'            => $roomId,
                'title'              => $title,
                'status'             => $status,
                'created_ids'        => $createdIds,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to create recurring booking series.',
            ]);
        }

        $db->transCommit();

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => "Recurring booking series of {$totalOccurrences} meetings created successfully.",
            'data'    => [
                'recurring_group_id' => $recurringGroupId,
                'recurrence_pattern' => $recurrencePattern,
                'total_occurrences'  => $totalOccurrences,
                'created_ids'        => $createdIds,
                'created_bookings'   => $createdBookings,
            ],
        ]);
    }
}
