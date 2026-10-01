<?php

namespace App\Controllers;

use App\Models\AuditLog as AuditLogModel;
use App\Models\Booking as BookingModel;
use App\Models\BookingParticipant as BookingParticipantModel;
use App\Models\CateringRequest as CateringRequestModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class CateringRequest extends BaseController
{
    protected CateringRequestModel $cateringRequestModel;
    protected BookingModel $bookingModel;
    protected BookingParticipantModel $bookingParticipantModel;
    protected UserModel $userModel;
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->cateringRequestModel    = new CateringRequestModel();
        $this->bookingModel            = new BookingModel();
        $this->bookingParticipantModel = new BookingParticipantModel();
        $this->userModel               = new UserModel();
        $this->auditLogModel           = new AuditLogModel();
    }

    /**
     * Legacy / general listing of catering requests (Protected).
     *
     * GET /catering-requests
     */
    public function index(): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $roleName = (string) ($session->get('role_name') ?? '');
        if (empty($roleName)) {
            $user = $this->userModel->find($currentUserId);
            if ($user && !empty($user['role_id'])) {
                $db = \Config\Database::connect();
                $roleRow = $db->table('roles')->where('id', $user['role_id'])->get()->getRowArray();
                if ($roleRow) {
                    $roleName = $roleRow['name'];
                }
            }
        }

        $builder = $this->cateringRequestModel->builder();
        $builder->select('
            catering_requests.*,
            users.first_name as requester_first_name,
            users.last_name as requester_last_name,
            users.email as requester_email,
            bookings.title as booking_title,
            bookings.room_id,
            bookings.start_time as booking_start_time,
            bookings.end_time as booking_end_time,
            bookings.status as booking_status,
            rooms.name as room_name,
            rooms.room_code
        ')
        ->join('users', 'users.id = catering_requests.requested_by', 'left')
        ->join('bookings', 'bookings.id = catering_requests.booking_id', 'left')
        ->join('rooms', 'rooms.id = bookings.room_id', 'left');

        // Admin & Facilities Manager see all; others see their own requested or organized
        if (!in_array($roleName, ['Admin', 'Facilities Manager'], true)) {
            $builder->groupStart()
                ->where('catering_requests.requested_by', $currentUserId)
                ->orWhere('bookings.user_id', $currentUserId)
            ->groupEnd();
        }

        $builder->orderBy('catering_requests.id', 'ASC');
        $rows = $builder->get()->getResultArray();
        $formatted = array_map([$this->cateringRequestModel, 'formatCateringRow'], $rows);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $formatted,
        ]);
    }

    /**
     * List all catering requests for a specific booking occurrence.
     *
     * GET /api/bookings/{bookingId}/catering
     */
    public function bookingIndex(int $bookingId): ResponseInterface
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

        $auth = $this->resolveCateringAuthorization($currentUserId, $booking);
        if (!$auth['can_view']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to view catering for this booking.',
            ]);
        }

        $requests = $this->cateringRequestModel->getByBookingId($bookingId);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'booking_id'  => $bookingId,
                'can_manage'  => $auth['can_manage'],
                'total_count' => count($requests),
                'catering'    => $requests,
            ],
        ]);
    }

    /**
     * Create a new catering request for a booking occurrence.
     *
     * POST /api/bookings/{bookingId}/catering
     */
    public function create(int $bookingId): ResponseInterface
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

        // Validate booking status
        if (in_array($booking['status'], ['cancelled', 'rejected'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Cannot request catering for a {$booking['status']} booking.",
            ]);
        }

        $auth = $this->resolveCateringAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to request catering for this booking.',
            ]);
        }

        $raw = $this->request->getJSON(true) ?? $this->request->getPost();

        // Validate catering_type
        $cateringType = trim((string) ($raw['catering_type'] ?? ''));
        if ($cateringType === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Catering type is required.',
                'errors'  => ['catering_type' => 'Catering type is required.'],
            ]);
        }
        if (mb_strlen($cateringType) > 100) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Catering type cannot exceed 100 characters.',
                'errors'  => ['catering_type' => 'Catering type cannot exceed 100 characters.'],
            ]);
        }

        // Validate quantity
        if (!isset($raw['quantity']) || !is_numeric($raw['quantity'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Catering quantity is required.',
                'errors'  => ['quantity' => 'Catering quantity is required.'],
            ]);
        }
        $quantity = (int) $raw['quantity'];
        if ($quantity <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Quantity must be greater than zero.',
                'errors'  => ['quantity' => 'Quantity must be greater than zero.'],
            ]);
        }

        $specialRequirements = isset($raw['special_requirements']) && trim((string) $raw['special_requirements']) !== ''
            ? trim((string) $raw['special_requirements'])
            : null;

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'booking_id'           => $bookingId,
            'requested_by'         => $currentUserId,
            'catering_type'        => $cateringType,
            'quantity'             => $quantity,
            'special_requirements' => $specialRequirements,
            'status'               => 'pending',
            'rejection_reason'     => null,
            'requested_at'         => $now,
        ];

        $newId = $this->cateringRequestModel->insert($insertData);
        if (!$newId) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to create catering request.',
            ]);
        }

        // Structured Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'catering_requested',
            'table_name' => 'catering_requests',
            'record_id'  => (int) $newId,
            'old_values' => null,
            'new_values' => json_encode($insertData),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $now,
        ]);

        $created = $this->cateringRequestModel->getById($newId);

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Catering request created successfully.',
            'data'    => $created,
        ]);
    }

    /**
     * Get details for a single catering request.
     *
     * GET /api/catering-requests/{id}
     */
    public function show(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $catering = $this->cateringRequestModel->getById($id);
        if ($catering === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request not found.',
            ]);
        }

        $booking = $this->bookingModel->find($catering['booking_id']);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Associated booking not found.',
            ]);
        }

        $auth = $this->resolveCateringAuthorization($currentUserId, $booking);
        if (!$auth['can_view']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to view this catering request.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => array_merge($catering, [
                'can_manage' => $auth['can_manage'],
            ]),
        ]);
    }

    /**
     * Update an existing pending catering request.
     *
     * PUT /api/catering-requests/{id}
     */
    public function update(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $catering = $this->cateringRequestModel->find($id);
        if ($catering === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request not found.',
            ]);
        }

        $booking = $this->bookingModel->find($catering['booking_id']);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Associated booking not found.',
            ]);
        }

        $auth = $this->resolveCateringAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to update this catering request.',
            ]);
        }

        if ($catering['status'] !== 'pending') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Only pending catering requests can be updated. Current status is '{$catering['status']}'.",
            ]);
        }

        $raw = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $updateData = [];

        // Validate catering_type if provided
        if (isset($raw['catering_type'])) {
            $cateringType = trim((string) $raw['catering_type']);
            if ($cateringType === '') {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => 'Catering type cannot be empty.',
                    'errors'  => ['catering_type' => 'Catering type cannot be empty.'],
                ]);
            }
            if (mb_strlen($cateringType) > 100) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => 'Catering type cannot exceed 100 characters.',
                    'errors'  => ['catering_type' => 'Catering type cannot exceed 100 characters.'],
                ]);
            }
            $updateData['catering_type'] = $cateringType;
        }

        // Validate quantity if provided
        if (isset($raw['quantity'])) {
            if (!is_numeric($raw['quantity'])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => 'Quantity must be a valid number.',
                    'errors'  => ['quantity' => 'Quantity must be a valid number.'],
                ]);
            }
            $quantity = (int) $raw['quantity'];
            if ($quantity <= 0) {
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => 'Quantity must be greater than zero.',
                    'errors'  => ['quantity' => 'Quantity must be greater than zero.'],
                ]);
            }
            $updateData['quantity'] = $quantity;
        }

        // Special requirements
        if (array_key_exists('special_requirements', $raw)) {
            $updateData['special_requirements'] = !empty(trim((string) $raw['special_requirements']))
                ? trim((string) $raw['special_requirements'])
                : null;
        }

        if (empty($updateData)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'No valid fields provided for update.',
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $this->cateringRequestModel->update($id, $updateData);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'catering_updated',
            'table_name' => 'catering_requests',
            'record_id'  => (int) $id,
            'old_values' => json_encode(array_intersect_key($catering, $updateData)),
            'new_values' => json_encode($updateData),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $now,
        ]);

        $updated = $this->cateringRequestModel->getById($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Catering request updated successfully.',
            'data'    => $updated,
        ]);
    }

    /**
     * Cancel a catering request (DELETE semantics preserving audit history).
     *
     * DELETE /api/catering-requests/{id}
     */
    public function delete(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $catering = $this->cateringRequestModel->find($id);
        if ($catering === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request not found.',
            ]);
        }

        $booking = $this->bookingModel->find($catering['booking_id']);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Associated booking not found.',
            ]);
        }

        $auth = $this->resolveCateringAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to cancel this catering request.',
            ]);
        }

        // Lifecycle validation for cancellation
        if ($catering['status'] === 'cancelled') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request is already cancelled.',
            ]);
        }

        if ($catering['status'] === 'rejected') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot cancel a rejected catering request.',
            ]);
        }

        if ($catering['status'] === 'completed') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot cancel a completed catering request.',
            ]);
        }

        // Valid cancel from 'pending' or 'approved'
        $now = date('Y-m-d H:i:s');
        $this->cateringRequestModel->update($id, [
            'status' => 'cancelled',
        ]);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'catering_cancelled',
            'table_name' => 'catering_requests',
            'record_id'  => (int) $id,
            'old_values' => json_encode(['status' => $catering['status']]),
            'new_values' => json_encode(['status' => 'cancelled']),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $now,
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Catering request cancelled successfully.',
        ]);
    }

    /**
     * Approve a catering request (Admin / Facilities Manager only).
     *
     * POST /api/catering-requests/{id}/approve
     */
    public function approve(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $roleName = (string) ($session->get('role_name') ?? '');
        if (!in_array($roleName, ['Admin', 'Facilities Manager'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Only Admins and Facilities Managers can approve catering requests.',
            ]);
        }

        $catering = $this->cateringRequestModel->find($id);
        if ($catering === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request not found.',
            ]);
        }

        $booking = $this->bookingModel->find($catering['booking_id']);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Associated booking not found.',
            ]);
        }

        if (in_array($booking['status'], ['cancelled', 'rejected'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Cannot approve catering for a {$booking['status']} booking.",
            ]);
        }

        if ($catering['status'] === 'approved') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request is already approved.',
            ]);
        }

        if ($catering['status'] !== 'pending') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Cannot approve a {$catering['status']} catering request. Only pending requests can be approved.",
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $this->cateringRequestModel->update($id, [
            'status'           => 'approved',
            'rejection_reason' => null,
        ]);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'catering_approved',
            'table_name' => 'catering_requests',
            'record_id'  => (int) $id,
            'old_values' => json_encode(['status' => $catering['status']]),
            'new_values' => json_encode(['status' => 'approved']),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $now,
        ]);

        $updated = $this->cateringRequestModel->getById($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Catering request approved successfully.',
            'data'    => $updated,
        ]);
    }

    /**
     * Reject a catering request with a required reason (Admin / Facilities Manager only).
     *
     * POST /api/catering-requests/{id}/reject
     */
    public function reject(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $roleName = (string) ($session->get('role_name') ?? '');
        if (!in_array($roleName, ['Admin', 'Facilities Manager'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Only Admins and Facilities Managers can reject catering requests.',
            ]);
        }

        $catering = $this->cateringRequestModel->find($id);
        if ($catering === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request not found.',
            ]);
        }

        if ($catering['status'] === 'rejected') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request is already rejected.',
            ]);
        }

        if ($catering['status'] !== 'pending') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Cannot reject a {$catering['status']} catering request. Only pending requests can be rejected.",
            ]);
        }

        // Validate rejection reason
        $raw = $this->request->getJSON(true) ?? $this->request->getPost();
        $reason = trim((string) ($raw['reason'] ?? $raw['rejection_reason'] ?? ''));

        if (mb_strlen($reason) < 3) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'A valid rejection reason is required (minimum 3 characters).',
                'errors'  => ['reason' => 'A valid rejection reason is required (minimum 3 characters).'],
            ]);
        }

        if (mb_strlen($reason) > 500) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Rejection reason cannot exceed 500 characters.',
                'errors'  => ['reason' => 'Rejection reason cannot exceed 500 characters.'],
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $this->cateringRequestModel->update($id, [
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'catering_rejected',
            'table_name' => 'catering_requests',
            'record_id'  => (int) $id,
            'old_values' => json_encode(['status' => $catering['status']]),
            'new_values' => json_encode([
                'status'           => 'rejected',
                'rejection_reason' => $reason,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $now,
        ]);

        $updated = $this->cateringRequestModel->getById($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Catering request rejected successfully.',
            'data'    => $updated,
        ]);
    }

    /**
     * Mark an approved catering request as completed (Admin / Facilities Manager only).
     *
     * POST /api/catering-requests/{id}/complete
     */
    public function complete(int $id): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $roleName = (string) ($session->get('role_name') ?? '');
        if (!in_array($roleName, ['Admin', 'Facilities Manager'], true)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Only Admins and Facilities Managers can mark catering requests as completed.',
            ]);
        }

        $catering = $this->cateringRequestModel->find($id);
        if ($catering === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request not found.',
            ]);
        }

        if ($catering['status'] === 'completed') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Catering request is already completed.',
            ]);
        }

        if ($catering['status'] !== 'approved') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Cannot complete a {$catering['status']} catering request. Only approved requests can be completed.",
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $this->cateringRequestModel->update($id, [
            'status' => 'completed',
        ]);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'catering_completed',
            'table_name' => 'catering_requests',
            'record_id'  => (int) $id,
            'old_values' => json_encode(['status' => $catering['status']]),
            'new_values' => json_encode(['status' => 'completed']),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $now,
        ]);

        $updated = $this->cateringRequestModel->getById($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Catering request marked as completed.',
            'data'    => $updated,
        ]);
    }

    /**
     * Resolve permissions for viewing and managing catering for a booking.
     *
     * @param int $userId
     * @param array $booking
     * @return array{can_view: bool, can_manage: bool}
     */
    protected function resolveCateringAuthorization(int $userId, array $booking): array
    {
        if (empty($userId)) {
            return ['can_view' => false, 'can_manage' => false];
        }

        $organizerId = (int) $booking['user_id'];
        $currentUserRoleName = (string) (service('session')->get('role_name') ?? '');
        if (empty($currentUserRoleName)) {
            $userRecord = $this->userModel->find($userId);
            if ($userRecord && !empty($userRecord['role_id'])) {
                $db = \Config\Database::connect();
                $roleRow = $db->table('roles')->where('id', $userRecord['role_id'])->get()->getRowArray();
                if ($roleRow) {
                    $currentUserRoleName = $roleRow['name'];
                }
            }
        }

        // 1. Admin or Facilities Manager: full access
        if (in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true)) {
            return ['can_view' => true, 'can_manage' => true];
        }

        // 2. Booking Organizer: full management of catering for own booking
        if ($userId === $organizerId) {
            return ['can_view' => true, 'can_manage' => true];
        }

        // 3. Department Manager: can manage if in same department as organizer
        if ($currentUserRoleName === 'Manager') {
            $currentUserRecord = $this->userModel->find($userId);
            $organizer = $this->userModel->find($organizerId);
            $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;
            $organizerDeptId   = !empty($organizer['department_id']) ? (int) $organizer['department_id'] : null;
            if ($currentUserDeptId !== null && $organizerDeptId !== null && $currentUserDeptId === $organizerDeptId) {
                return ['can_view' => true, 'can_manage' => true];
            }
        }

        // 4. Accepted Participant: read-only access (can_manage = false)
        $participant = $this->bookingParticipantModel
            ->where('booking_id', (int) $booking['id'])
            ->where('user_id', $userId)
            ->first();

        if ($participant !== null && ($participant['response_status'] ?? '') === 'accepted') {
            return ['can_view' => true, 'can_manage' => false];
        }

        // 5. Non-participant or declined: denied
        return ['can_view' => false, 'can_manage' => false];
    }
}