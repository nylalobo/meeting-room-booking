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
     * Create a new booking.
     */
    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];

        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');

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
}