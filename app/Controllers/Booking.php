<?php

namespace App\Controllers;

use App\Models\AuditLog as AuditLogModel;
use App\Models\Booking as BookingModel;
use App\Models\BookingCheckin as BookingCheckinModel;
use App\Models\BookingParticipant as BookingParticipantModel;
use App\Models\Department as DepartmentModel;
use App\Models\Location as LocationModel;
use App\Models\Room as RoomModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Booking extends BaseController
{
    protected BookingModel $bookingModel;
    protected RoomModel $roomModel;
    protected UserModel $userModel;
    protected DepartmentModel $departmentModel;
    protected LocationModel $locationModel;
    protected AuditLogModel $auditLogModel;
    protected BookingCheckinModel $bookingCheckinModel;
    protected BookingParticipantModel $bookingParticipantModel;

    public function __construct()
    {
        $this->bookingModel            = new BookingModel();
        $this->roomModel               = new RoomModel();
        $this->userModel               = new UserModel();
        $this->departmentModel         = new DepartmentModel();
        $this->locationModel           = new LocationModel();
        $this->auditLogModel           = new AuditLogModel();
        $this->bookingCheckinModel     = new BookingCheckinModel();
        $this->bookingParticipantModel = new BookingParticipantModel();
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

            $booking['can_view_attendance'] = $this->canUserViewAttendance(
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
     * Cancel recurring booking series (mode: all or future).
     *
     * DELETE /api/bookings/series/(:segment)
     */
    public function deleteSeries(string $groupId): ResponseInterface
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

        // Validate mode
        $mode = strtolower(trim((string) ($this->request->getGet('mode') ?? $this->request->getJSON(true)['mode'] ?? 'all')));
        if ($mode === '') {
            $mode = 'all';
        }
        if (!in_array($mode, ['all', 'future'], true)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => "Invalid mode. Supported modes: 'all', 'future'.",
            ]);
        }

        // Authorization check
        $seriesOrganizerId = (int) $rawOccurrences[0]['user_id'];
        $organizer = $this->userModel->find($seriesOrganizerId);
        $currentUserRecord = $this->userModel->find($currentUserId);
        if (empty($currentUserRoleName) && !empty($currentUserRecord['role_id'])) {
            $db = \Config\Database::connect();
            $roleRow = $db->table('roles')->where('id', $currentUserRecord['role_id'])->get()->getRowArray();
            if ($roleRow) {
                $currentUserRoleName = $roleRow['name'];
            }
        }
        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;
        $organizerDeptId   = !empty($organizer['department_id']) ? (int) $organizer['department_id'] : null;

        $isAuthorized = false;
        if ($currentUserId === $seriesOrganizerId) {
            $isAuthorized = true;
        } elseif (in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true)) {
            $isAuthorized = true;
        } elseif ($currentUserRoleName === 'Manager') {
            if ($currentUserDeptId !== null && $organizerDeptId !== null && $currentUserDeptId === $organizerDeptId) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. You do not have permission to manage this recurring series.',
            ]);
        }

        $fromParam = trim((string) ($this->request->getGet('from') ?? $this->request->getJSON(true)['from'] ?? ''));
        $cutoff = null;
        if ($fromParam !== '') {
            $fromTs = strtotime($fromParam);
            if ($fromTs === false) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status'  => 'error',
                    'message' => "Invalid 'from' datetime format.",
                ]);
            }
            $cutoff = date('Y-m-d H:i:s', $fromTs);
        } else {
            $cutoff = date('Y-m-d H:i:s');
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        $cancelResult = $this->bookingModel->cancelRecurringSeries($groupId, $mode, $cutoff);
        $affectedIds = $cancelResult['affected_ids'];
        $affectedCount = $cancelResult['affected_count'];

        $recordId = !empty($affectedIds) ? $affectedIds[0] : (int) $rawOccurrences[0]['id'];
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'recurring_series_cancelled_' . $mode,
            'table_name' => 'bookings',
            'record_id'  => $recordId,
            'old_values' => null,
            'new_values' => json_encode([
                'recurring_group_id' => $groupId,
                'mode'               => $mode,
                'affected_count'     => $affectedCount,
                'affected_ids'       => $affectedIds,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to cancel recurring booking series.',
            ]);
        }

        $db->transCommit();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Recurring booking series cancelled successfully ({$affectedCount} occurrences affected).",
            'data'    => [
                'recurring_group_id'        => $groupId,
                'mode'                      => $mode,
                'affected_count'            => $affectedCount,
                'affected_occurrence_count' => $affectedCount,
                'affected_ids'              => $affectedIds,
                'affected_booking_ids'      => $affectedIds,
            ],
        ]);
    }

    /**
     * Detach a single booking from its recurring series.
     *
     * POST /api/bookings/(:num)/detach
     */
    public function detach(int $id): ResponseInterface
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

        if (empty($booking['recurring_group_id'])) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'This booking is not part of a recurring series.',
            ]);
        }

        // Authorization check
        $bookingUserId = (int) $booking['user_id'];
        $organizer = $this->userModel->find($bookingUserId);
        $currentUserRecord = $this->userModel->find($currentUserId);
        if (empty($currentUserRoleName) && !empty($currentUserRecord['role_id'])) {
            $db = \Config\Database::connect();
            $roleRow = $db->table('roles')->where('id', $currentUserRecord['role_id'])->get()->getRowArray();
            if ($roleRow) {
                $currentUserRoleName = $roleRow['name'];
            }
        }
        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;
        $organizerDeptId   = !empty($organizer['department_id']) ? (int) $organizer['department_id'] : null;

        $isAuthorized = false;
        if ($currentUserId === $bookingUserId) {
            $isAuthorized = true;
        } elseif (in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true)) {
            $isAuthorized = true;
        } elseif ($currentUserRoleName === 'Manager') {
            if ($currentUserDeptId !== null && $organizerDeptId !== null && $currentUserDeptId === $organizerDeptId) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. You do not have permission to detach this booking.',
            ]);
        }

        $originalGroupId = $booking['recurring_group_id'];

        $db = \Config\Database::connect();
        $db->transBegin();

        $this->bookingModel->detachOccurrence($id);

        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_detached_from_series',
            'table_name' => 'bookings',
            'record_id'  => $id,
            'old_values' => json_encode([
                'recurring_group_id' => $originalGroupId,
                'recurrence_pattern' => $booking['recurrence_pattern'],
                'recurrence_index'   => $booking['recurrence_index'],
                'recurrence_total'   => $booking['recurrence_total'],
            ]),
            'new_values' => json_encode([
                'recurring_group_id' => null,
                'recurrence_pattern' => null,
                'recurrence_index'   => null,
                'recurrence_total'   => null,
                'original_group_id'  => $originalGroupId,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($db->transStatus() === false) {
            $db->transRollback();
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to detach booking from series.',
            ]);
        }

        $db->transCommit();

        $updatedBooking = $this->bookingModel->find($id);
        $updatedBooking['id']      = (int) $updatedBooking['id'];
        $updatedBooking['room_id'] = (int) $updatedBooking['room_id'];
        $updatedBooking['user_id'] = (int) $updatedBooking['user_id'];

        // Enrich response
        $room = $this->roomModel->find($updatedBooking['room_id']);
        $user = $this->userModel->find($updatedBooking['user_id']);

        $updatedBooking['room_name'] = $room['name'] ?? 'Unknown Room';
        $updatedBooking['room_code'] = $room['room_code'] ?? null;
        $updatedBooking['organizer_name'] = $user
            ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
            : 'Unknown User';
        $updatedBooking['organizer_email'] = $user['email'] ?? null;

        if ($user && !empty($user['department_id'])) {
            $dept = $this->departmentModel->find($user['department_id']);
            $updatedBooking['organizer_department_name'] = $dept['name'] ?? null;
            $updatedBooking['organizer_department_id']   = (int) $user['department_id'];
        } else {
            $updatedBooking['organizer_department_name'] = null;
            $updatedBooking['organizer_department_id']   = null;
        }

        if (!empty($updatedBooking['approver_id'])) {
            $approver = $this->userModel->find($updatedBooking['approver_id']);
            $updatedBooking['approver_name'] = $approver
                ? trim(($approver['first_name'] ?? '') . ' ' . ($approver['last_name'] ?? ''))
                : null;
            $updatedBooking['approver_email'] = $approver['email'] ?? null;
        } else {
            $updatedBooking['approver_name'] = null;
            $updatedBooking['approver_email'] = null;
        }

        $updatedBooking['can_approve'] = $this->canUserApproveBooking(
            $currentUserId,
            $currentUserRoleName,
            $currentUserDeptId,
            $updatedBooking,
            $user
        );

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Booking detached from recurring series successfully.',
            'data'    => $updatedBooking,
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

            $booking['can_view_attendance'] = $this->canUserViewAttendance(
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
     * Determine if a user can view attendance records for a specific booking.
     */
    protected function canUserViewAttendance(
        int $userId,
        string $userRoleName,
        ?int $userDeptId,
        array $booking,
        ?array $organizer
    ): bool {
        if (empty($userId)) {
            return false;
        }

        // Organizer can always view attendance
        if ((int) ($booking['user_id'] ?? 0) === $userId) {
            return true;
        }

        // Global roles: Admin and Facilities Manager
        if (in_array($userRoleName, ['Admin', 'Facilities Manager'], true)) {
            return true;
        }

        // Departmental Manager: Manager of the same department as the organizer
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

    /**
     * Check in to an approved booking within the valid time window.
     *
     * POST /api/bookings/(:num)/check-in
     */
    public function checkIn(int $bookingId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        if ($booking['status'] !== 'approved') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Cannot check into a booking with status '{$booking['status']}'. Only approved bookings can be checked into.",
            ]);
        }

        $now = time();
        $startTime = strtotime($booking['start_time']);
        $endTime   = strtotime($booking['end_time']);
        $checkinWindowStart = $startTime - (15 * 60);

        if ($now < $checkinWindowStart) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Check-in is not yet open. Check-in opens 15 minutes before the meeting start time.',
            ]);
        }

        if ($now > $endTime) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Check-in has closed. This meeting has already ended.',
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
            ->where('booking_id', $bookingId)
            ->where('user_id', $currentUserId)
            ->first();
        $isParticipant = ($participant !== null && $participant['response_status'] !== 'declined');

        if (!$isOrganizer && !$isElevatedRole && !$isParticipant) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You are not authorized to check into this meeting.',
            ]);
        }

        $activeCheckin = $this->bookingCheckinModel->getActiveCheckin($bookingId, $currentUserId);
        if ($activeCheckin !== null) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => 'User is already checked in to this meeting.',
                'data'    => $activeCheckin,
            ]);
        }

        $rawInput = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
        $allowedMethods = ['qr_code', 'manual', 'room_display'];
        $method = 'qr_code';
        if (!empty($rawInput['check_in_method']) && in_array($rawInput['check_in_method'], $allowedMethods, true)) {
            $method = $rawInput['check_in_method'];
        }

        $serverNow = date('Y-m-d H:i:s');
        $checkinData = [
            'booking_id'      => $bookingId,
            'user_id'         => $currentUserId,
            'check_in_time'   => $serverNow,
            'check_out_time'  => null,
            'check_in_method' => $method,
            'status'          => 'checked_in',
        ];

        $checkinId = $this->bookingCheckinModel->insert($checkinData);
        if (!$checkinId) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to record check-in.',
                'errors'  => $this->bookingCheckinModel->errors(),
            ]);
        }

        $createdCheckin = $this->bookingCheckinModel->find($checkinId);

        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_checked_in',
            'table_name' => 'bookings',
            'record_id'  => $bookingId,
            'old_values' => null,
            'new_values' => json_encode([
                'checkin_id'      => (int) $checkinId,
                'user_id'         => $currentUserId,
                'check_in_time'   => $serverNow,
                'check_in_method' => $method,
                'status'          => 'checked_in',
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $serverNow,
        ]);

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Check-in successful.',
            'data'    => $createdCheckin,
        ]);
    }

    /**
     * Check out of an active check-in for a booking.
     *
     * POST /api/bookings/(:num)/check-out
     */
    public function checkOut(int $bookingId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
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

        $targetUserId = $currentUserId;
        $rawInput = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
        if (!empty($rawInput['user_id'])) {
            $requestedUserId = (int) $rawInput['user_id'];
            if ($requestedUserId > 0 && $requestedUserId !== $currentUserId) {
                $isElevatedRole = in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true);
                if (!$isElevatedRole) {
                    return $this->response->setStatusCode(403)->setJSON([
                        'status'  => 'error',
                        'message' => 'Forbidden. You can only check out your own session.',
                    ]);
                }
                $targetUserId = $requestedUserId;
            }
        }

        $activeCheckin = $this->bookingCheckinModel->getActiveCheckin($bookingId, $targetUserId);
        if ($activeCheckin === null) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'No active check-in found for this booking.',
            ]);
        }

        $serverNow = date('Y-m-d H:i:s');
        $updateSuccess = $this->bookingCheckinModel->update($activeCheckin['id'], [
            'check_out_time' => $serverNow,
            'status'         => 'checked_out',
        ]);

        if (!$updateSuccess) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to record check-out.',
                'errors'  => $this->bookingCheckinModel->errors(),
            ]);
        }

        $updatedCheckin = $this->bookingCheckinModel->find($activeCheckin['id']);

        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_checked_out',
            'table_name' => 'bookings',
            'record_id'  => $bookingId,
            'old_values' => json_encode([
                'checkin_id'     => (int) $activeCheckin['id'],
                'status'         => $activeCheckin['status'],
                'check_out_time' => $activeCheckin['check_out_time'],
            ]),
            'new_values' => json_encode([
                'checkin_id'     => (int) $activeCheckin['id'],
                'user_id'        => $targetUserId,
                'status'         => 'checked_out',
                'check_out_time' => $serverNow,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $serverNow,
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Check-out successful.',
            'data'    => $updatedCheckin,
        ]);
    }

    /**
     * Get check-in and attendance records for a booking.
     *
     * GET /api/bookings/(:num)/check-ins
     */
    public function checkIns(int $bookingId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        $organizerId = (int) $booking['user_id'];
        $organizer = $this->userModel->find($organizerId);
        $currentUserRecord = $this->userModel->find($currentUserId);
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');
        if (empty($currentUserRoleName) && !empty($currentUserRecord['role_id'])) {
            $db = \Config\Database::connect();
            $roleRow = $db->table('roles')->where('id', $currentUserRecord['role_id'])->get()->getRowArray();
            if ($roleRow) {
                $currentUserRoleName = $roleRow['name'];
            }
        }

        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;
        $organizerDeptId   = !empty($organizer['department_id']) ? (int) $organizer['department_id'] : null;

        $canView = false;
        if ($currentUserId === $organizerId) {
            $canView = true;
        } elseif (in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true)) {
            $canView = true;
        } elseif ($currentUserRoleName === 'Manager') {
            if ($currentUserDeptId !== null && $organizerDeptId !== null && $currentUserDeptId === $organizerDeptId) {
                $canView = true;
            }
        }

        if (!$canView) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to view attendance for this booking.',
            ]);
        }

        $records = $this->bookingCheckinModel->getCheckinsWithUsers($bookingId);
        $formatted = [];
        foreach ($records as $r) {
            $formatted[] = [
                'id'              => (int) $r['id'],
                'booking_id'      => (int) $r['booking_id'],
                'user_id'         => (int) $r['user_id'],
                'user_name'       => trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')),
                'first_name'      => $r['first_name'] ?? '',
                'last_name'       => $r['last_name'] ?? '',
                'email'           => $r['email'] ?? '',
                'check_in_time'   => $r['check_in_time'],
                'check_out_time'  => $r['check_out_time'],
                'check_in_method' => $r['check_in_method'],
                'status'          => $r['status'],
                'created_at'      => $r['created_at'] ?? null,
                'updated_at'      => $r['updated_at'] ?? null,
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $formatted,
        ]);
    }

    /**
     * Get comprehensive attendance tracking and management data for a booking.
     *
     * GET /api/bookings/(:num)/attendance
     */
    public function attendance(int $bookingId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        $organizerId = (int) $booking['user_id'];
        $organizer = $this->userModel->find($organizerId);
        $currentUserRecord = $this->userModel->find($currentUserId);
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');
        if (empty($currentUserRoleName) && !empty($currentUserRecord['role_id'])) {
            $db = \Config\Database::connect();
            $roleRow = $db->table('roles')->where('id', $currentUserRecord['role_id'])->get()->getRowArray();
            if ($roleRow) {
                $currentUserRoleName = $roleRow['name'];
            }
        }

        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;
        $canView = $this->canUserViewAttendance(
            $currentUserId,
            $currentUserRoleName,
            $currentUserDeptId,
            $booking,
            $organizer
        );

        if (!$canView) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to view attendance for this booking.',
            ]);
        }

        // Room and Location details
        $room = $this->roomModel->find($booking['room_id']);
        $locationName = null;
        if ($room && !empty($room['location_id'])) {
            $location = $this->locationModel->find($room['location_id']);
            $locationName = $location['name'] ?? null;
        }

        $organizerName = $organizer
            ? trim(($organizer['first_name'] ?? '') . ' ' . ($organizer['last_name'] ?? ''))
            : '';
        if ($organizerName === '') {
            $organizerName = $organizer['email'] ?? ('User #' . $organizerId);
        }

        // Attendees dataset building (organizer + participants + checkins)
        $attendeesMap = [];

        // 1. Organizer is always represented (even if absent from booking_participants)
        $attendeesMap[$organizerId] = [
            'user_id'          => $organizerId,
            'name'             => $organizerName,
            'email'            => $organizer['email'] ?? '',
            'participant_type' => 'organizer',
            'response_status'  => 'accepted',
            'is_invited'       => true,
        ];

        // 2. Booking Participants
        $participants = $this->bookingParticipantModel
            ->select('booking_participants.*, users.first_name, users.last_name, users.email')
            ->join('users', 'users.id = booking_participants.user_id', 'left')
            ->where('booking_participants.booking_id', $bookingId)
            ->findAll();

        foreach ($participants as $p) {
            $uid = (int) $p['user_id'];
            $pName = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
            if ($pName === '') {
                $pName = $p['email'] ?? ('User #' . $uid);
            }

            if (isset($attendeesMap[$uid])) {
                // Merge with existing record (e.g. organizer)
                if (!empty($p['participant_type'])) {
                    $attendeesMap[$uid]['participant_type'] = $p['participant_type'];
                }
                if (!empty($p['response_status'])) {
                    $attendeesMap[$uid]['response_status'] = $p['response_status'];
                }
            } else {
                $attendeesMap[$uid] = [
                    'user_id'          => $uid,
                    'name'             => $pName,
                    'email'            => $p['email'] ?? '',
                    'participant_type' => $p['participant_type'] ?? 'participant',
                    'response_status'  => $p['response_status'] ?? 'pending',
                    'is_invited'       => true,
                ];
            }
        }

        // 3. Booking Check-ins (strictly isolated by booking_id)
        $checkinRecords = $this->bookingCheckinModel
            ->select('booking_checkins.*, users.first_name, users.last_name, users.email')
            ->join('users', 'users.id = booking_checkins.user_id', 'left')
            ->where('booking_checkins.booking_id', $bookingId)
            ->orderBy('booking_checkins.id', 'ASC')
            ->findAll();

        $latestCheckinByUser = [];
        foreach ($checkinRecords as $c) {
            $uid = (int) $c['user_id'];
            $latestCheckinByUser[$uid] = $c;
        }

        // Account for guest check-in records present in booking_checkins
        foreach ($latestCheckinByUser as $uid => $c) {
            if (!isset($attendeesMap[$uid])) {
                $guestName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                if ($guestName === '') {
                    $guestName = $c['email'] ?? ('User #' . $uid);
                }
                $attendeesMap[$uid] = [
                    'user_id'          => $uid,
                    'name'             => $guestName,
                    'email'            => $c['email'] ?? '',
                    'participant_type' => 'guest',
                    'response_status'  => 'accepted',
                    'is_invited'       => false,
                ];
            }
        }

        // Server current timestamp
        $nowTs = time();

        $attendees = [];
        $totalInvited = 0;
        $totalCheckedIn = 0;
        $totalCheckedOut = 0;
        $currentlyCheckedIn = 0;
        $notCheckedIn = 0;
        $totalDeclined = 0;

        foreach ($attendeesMap as $uid => $att) {
            $isInvited = !empty($att['is_invited']);
            if ($isInvited) {
                $totalInvited++;
                if (($att['response_status'] ?? '') === 'declined') {
                    $totalDeclined++;
                }
            }

            $hasCheckin = isset($latestCheckinByUser[$uid]);
            if ($hasCheckin) {
                $c = $latestCheckinByUser[$uid];
                $status = in_array($c['status'], ['checked_in', 'checked_out', 'auto_completed'], true)
                    ? $c['status']
                    : 'checked_in';

                $checkInTime = $c['check_in_time'];
                $checkOutTime = $c['check_out_time'];
                $checkInMethod = $c['check_in_method'] ?? 'qr_code';

                $totalCheckedIn++;
                if ($status === 'checked_out') {
                    $totalCheckedOut++;
                } elseif ($status === 'checked_in') {
                    $currentlyCheckedIn++;
                }

                $isLate = false;
                if (!empty($checkInTime) && !empty($booking['start_time'])) {
                    $isLate = (strtotime($checkInTime) > strtotime($booking['start_time']));
                }

                $durationInfo = $this->formatAttendanceDuration($checkInTime, $checkOutTime, $status, $nowTs);

                $attendees[] = [
                    'user_id'            => $uid,
                    'name'               => $att['name'],
                    'email'              => $att['email'],
                    'participant_type'   => $att['participant_type'],
                    'response_status'    => $att['response_status'],
                    'attendance_status'  => $status,
                    'check_in_time'      => $checkInTime,
                    'check_out_time'     => $checkOutTime,
                    'check_in_method'    => $checkInMethod,
                    'is_late'            => $isLate,
                    'duration_minutes'   => $durationInfo['minutes'],
                    'duration_formatted' => $durationInfo['formatted'],
                ];
            } else {
                if ($isInvited) {
                    $notCheckedIn++;
                }

                $attendees[] = [
                    'user_id'            => $uid,
                    'name'               => $att['name'],
                    'email'              => $att['email'],
                    'participant_type'   => $att['participant_type'],
                    'response_status'    => $att['response_status'],
                    'attendance_status'  => 'not_checked_in',
                    'check_in_time'      => null,
                    'check_out_time'     => null,
                    'check_in_method'    => null,
                    'is_late'            => false,
                    'duration_minutes'   => null,
                    'duration_formatted' => null,
                ];
            }
        }

        // Sort: Organizer first, then alphabetical by name
        usort($attendees, function ($a, $b) {
            if ($a['participant_type'] === 'organizer' && $b['participant_type'] !== 'organizer') {
                return -1;
            }
            if ($b['participant_type'] === 'organizer' && $a['participant_type'] !== 'organizer') {
                return 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'booking' => [
                    'booking_id'     => (int) $booking['id'],
                    'title'          => $booking['title'],
                    'room_id'        => (int) $booking['room_id'],
                    'room_name'      => $room['name'] ?? 'Unknown Room',
                    'room_code'      => $room['room_code'] ?? null,
                    'location_name'  => $locationName,
                    'start_time'     => $booking['start_time'],
                    'end_time'       => $booking['end_time'],
                    'status'         => $booking['status'],
                    'organizer_name' => $organizerName,
                ],
                'summary' => [
                    'total_invited'        => $totalInvited,
                    'total_checked_in'     => $totalCheckedIn,
                    'total_checked_out'    => $totalCheckedOut,
                    'currently_checked_in' => $currentlyCheckedIn,
                    'not_checked_in'       => $notCheckedIn,
                    'total_declined'       => $totalDeclined,
                ],
                'attendees' => $attendees,
            ],
        ]);
    }

    /**
     * Format duration into minutes and human-readable string.
     *
     * @param string|null $checkInTime
     * @param string|null $checkOutTime
     * @param string $attendanceStatus
     * @param int $nowTs
     * @return array{minutes: int|null, formatted: string|null}
     */
    protected function formatAttendanceDuration(?string $checkInTime, ?string $checkOutTime, string $attendanceStatus, int $nowTs): array
    {
        if (empty($checkInTime) || $attendanceStatus === 'not_checked_in') {
            return [
                'minutes'   => null,
                'formatted' => null,
            ];
        }

        $inTs = strtotime($checkInTime);
        if ($inTs === false) {
            return [
                'minutes'   => null,
                'formatted' => null,
            ];
        }

        if (!empty($checkOutTime)) {
            $outTs = strtotime($checkOutTime);
            $diffSec = max(0, ($outTs !== false ? $outTs - $inTs : 0));
            $minutes = (int) round($diffSec / 60);

            return [
                'minutes'   => $minutes,
                'formatted' => $this->formatMinutesHuman($minutes),
            ];
        }

        if ($attendanceStatus === 'checked_in') {
            $diffSec = max(0, $nowTs - $inTs);
            $minutes = (int) round($diffSec / 60);

            return [
                'minutes'   => $minutes,
                'formatted' => 'In progress (' . $this->formatMinutesHuman($minutes) . ')',
            ];
        }

        return [
            'minutes'   => 0,
            'formatted' => '0 mins',
        ];
    }

    /**
     * Convert minutes to human readable string.
     */
    protected function formatMinutesHuman(int $minutes): string
    {
        if ($minutes < 0) {
            $minutes = 0;
        }
        if ($minutes < 60) {
            return $minutes . ' mins';
        }

        $hours = intdiv($minutes, 60);
        $remMinutes = $minutes % 60;

        $hrStr = $hours === 1 ? '1 hr' : "{$hours} hrs";

        if ($remMinutes === 0) {
            return $hrStr;
        }

        return "{$hrStr} {$remMinutes} mins";
    }
}
