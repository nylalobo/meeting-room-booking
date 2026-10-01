<?php

namespace App\Controllers;

use App\Models\AuditLog as AuditLogModel;
use App\Models\Booking as BookingModel;
use App\Models\BookingParticipant as BookingParticipantModel;
use App\Models\BookingResource as BookingResourceModel;
use App\Models\Equipment as EquipmentModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class BookingResource extends BaseController
{
    protected BookingResourceModel $bookingResourceModel;
    protected BookingModel $bookingModel;
    protected EquipmentModel $equipmentModel;
    protected BookingParticipantModel $bookingParticipantModel;
    protected UserModel $userModel;
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->bookingResourceModel    = new BookingResourceModel();
        $this->bookingModel            = new BookingModel();
        $this->equipmentModel          = new EquipmentModel();
        $this->bookingParticipantModel = new BookingParticipantModel();
        $this->userModel               = new UserModel();
        $this->auditLogModel           = new AuditLogModel();
    }

    /**
     * List all resources assigned to a booking.
     *
     * GET /api/bookings/{booking_id}/resources
     */
    public function index(int $bookingId): ResponseInterface
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

        $auth = $this->resolveResourceAuthorization($currentUserId, $booking);
        if (!$auth['can_view']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to view resources for this booking.',
            ]);
        }

        $resources = $this->bookingResourceModel->getResourcesForBooking($bookingId);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'booking_id'  => $bookingId,
                'can_manage'  => $auth['can_manage'],
                'total_count' => count($resources),
                'resources'   => $resources,
            ],
        ]);
    }

    /**
     * Get details for a single assigned resource (IDOR-safe: scoped to booking_id and resource_id).
     *
     * GET /api/bookings/{booking_id}/resources/{resource_id}
     */
    public function show(int $bookingId, int $resourceId): ResponseInterface
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

        $auth = $this->resolveResourceAuthorization($currentUserId, $booking);
        if (!$auth['can_view']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to view resources for this booking.',
            ]);
        }

        $resource = $this->bookingResourceModel->getResource($bookingId, $resourceId);
        if ($resource === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Resource not found for this booking.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'booking_id' => $bookingId,
                'can_manage' => $auth['can_manage'],
                'resource'   => $resource,
            ],
        ]);
    }

    /**
     * Assign an equipment resource to a booking.
     *
     * POST /api/bookings/{booking_id}/resources
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

        $auth = $this->resolveResourceAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to assign resources to this booking.',
            ]);
        }

        if (in_array($booking['status'], ['cancelled', 'rejected'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot assign resources to a ' . $booking['status'] . ' booking.',
            ]);
        }

        $raw = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];

        // Quantity check (assets are physical units)
        if (isset($raw['quantity']) && (int) $raw['quantity'] !== 1) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'In this milestone, equipment represents individual physical assets. Quantity must be 1.',
            ]);
        }

        // Validate equipment_id
        if (empty($raw['equipment_id']) || !is_numeric($raw['equipment_id']) || (int) $raw['equipment_id'] <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => ['equipment_id' => 'Equipment ID is required and must be a positive integer.'],
            ]);
        }
        $equipmentId = (int) $raw['equipment_id'];

        // Check equipment exists in catalog
        $equipment = $this->equipmentModel->find($equipmentId);
        if ($equipment === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Equipment not found.',
            ]);
        }

        // Check catalog status
        if ($equipment['status'] !== 'available') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Equipment is currently marked as '{$equipment['status']}' and cannot be assigned.",
            ]);
        }

        // Validate notes
        $notes = trim(strip_tags((string) ($raw['notes'] ?? '')));
        if ($notes !== '' && mb_strlen($notes) > 1000) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => ['notes' => 'Notes cannot exceed 1000 characters.'],
            ]);
        }

        // Transaction with decisive conflict check immediately before insert
        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            $conflictCheck = $this->bookingResourceModel->checkConflict(
                $equipmentId,
                $booking['start_time'],
                $booking['end_time']
            );

            if ($conflictCheck['conflict']) {
                $db->transRollback();
                return $this->response->setStatusCode(409)->setJSON([
                    'status'              => 'error',
                    'message'             => $conflictCheck['message'] ?? 'Equipment is already reserved for an overlapping booking.',
                    'conflicting_booking' => $conflictCheck['conflicting_booking'],
                ]);
            }

            // Check if already assigned to this exact booking
            $existingAssignment = $this->bookingResourceModel
                ->where('booking_id', $bookingId)
                ->where('equipment_id', $equipmentId)
                ->first();

            if ($existingAssignment !== null) {
                $db->transRollback();
                return $this->response->setStatusCode(409)->setJSON([
                    'status'  => 'error',
                    'message' => 'This equipment is already assigned to this booking.',
                ]);
            }

            $insertData = [
                'booking_id'     => $bookingId,
                'equipment_id'   => $equipmentId,
                'status'         => 'reserved',
                'assigned_by'    => $currentUserId,
                'checked_out_at' => null,
                'checked_out_by' => null,
                'returned_at'    => null,
                'returned_to'    => null,
                'notes'          => $notes !== '' ? $notes : null,
            ];

            $resourceId = $this->bookingResourceModel->insert($insertData);
            if (!$resourceId) {
                $db->transRollback();
                return $this->response->setStatusCode(422)->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to assign resource.',
                    'errors'  => $this->bookingResourceModel->errors(),
                ]);
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062')) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status'  => 'error',
                    'message' => 'This equipment is already assigned to this booking.',
                ]);
            }
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to assign resource: ' . $e->getMessage(),
            ]);
        }

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_resource_assigned',
            'table_name' => 'booking_resources',
            'record_id'  => (int) $resourceId,
            'old_values' => null,
            'new_values' => json_encode([
                'booking_id'   => $bookingId,
                'equipment_id' => $equipmentId,
                'status'       => 'reserved',
                'assigned_by'  => $currentUserId,
                'notes'        => $notes !== '' ? $notes : null,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $created = $this->bookingResourceModel->getResource($bookingId, (int) $resourceId);

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Resource assigned successfully.',
            'data'    => [
                'resource' => $created,
            ],
        ]);
    }

    /**
     * Remove an assigned resource from a booking.
     *
     * DELETE /api/bookings/{booking_id}/resources/{resource_id}
     */
    public function delete(int $bookingId, int $resourceId): ResponseInterface
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

        $auth = $this->resolveResourceAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to remove resources from this booking.',
            ]);
        }

        $resource = $this->bookingResourceModel
            ->where('booking_id', $bookingId)
            ->where('id', $resourceId)
            ->first();

        if ($resource === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Resource not found for this booking.',
            ]);
        }

        if ($resource['status'] === 'checked_out') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot remove a resource that is currently checked out. Please return it first.',
            ]);
        }

        $this->bookingResourceModel->delete($resourceId);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_resource_removed',
            'table_name' => 'booking_resources',
            'record_id'  => (int) $resourceId,
            'old_values' => json_encode($resource),
            'new_values' => null,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Resource removed successfully.',
        ]);
    }

    /**
     * Mark an assigned resource as checked out.
     *
     * POST /api/bookings/{booking_id}/resources/{resource_id}/checkout
     */
    public function checkout(int $bookingId, int $resourceId): ResponseInterface
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

        $auth = $this->resolveResourceAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to check out resources for this booking.',
            ]);
        }

        $resource = $this->bookingResourceModel
            ->where('booking_id', $bookingId)
            ->where('id', $resourceId)
            ->first();

        if ($resource === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Resource not found for this booking.',
            ]);
        }

        if (in_array($booking['status'], ['cancelled', 'rejected'], true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot check out resource for a ' . $booking['status'] . ' booking.',
            ]);
        }

        if ($resource['status'] !== 'reserved') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Cannot check out resource with status '{$resource['status']}'. Only reserved resources can be checked out.",
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $this->bookingResourceModel->update($resourceId, [
            'status'         => 'checked_out',
            'checked_out_at' => $now,
            'checked_out_by' => $currentUserId,
        ]);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_resource_checked_out',
            'table_name' => 'booking_resources',
            'record_id'  => (int) $resourceId,
            'old_values' => json_encode(['status' => $resource['status']]),
            'new_values' => json_encode([
                'status'         => 'checked_out',
                'checked_out_at' => $now,
                'checked_out_by' => $currentUserId,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $updated = $this->bookingResourceModel->getResource($bookingId, $resourceId);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Resource checked out successfully.',
            'data'    => [
                'resource' => $updated,
            ],
        ]);
    }

    /**
     * Mark an assigned resource as returned.
     *
     * POST /api/bookings/{booking_id}/resources/{resource_id}/return
     */
    public function returnResource(int $bookingId, int $resourceId): ResponseInterface
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

        $auth = $this->resolveResourceAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to return resources for this booking.',
            ]);
        }

        $resource = $this->bookingResourceModel
            ->where('booking_id', $bookingId)
            ->where('id', $resourceId)
            ->first();

        if ($resource === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Resource not found for this booking.',
            ]);
        }

        if ($resource['status'] !== 'checked_out') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Cannot return resource with status '{$resource['status']}'. Only checked out resources can be returned.",
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $this->bookingResourceModel->update($resourceId, [
            'status'      => 'returned',
            'returned_at' => $now,
            'returned_to' => $currentUserId,
        ]);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_resource_returned',
            'table_name' => 'booking_resources',
            'record_id'  => (int) $resourceId,
            'old_values' => json_encode(['status' => $resource['status']]),
            'new_values' => json_encode([
                'status'      => 'returned',
                'returned_at' => $now,
                'returned_to' => $currentUserId,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $updated = $this->bookingResourceModel->getResource($bookingId, $resourceId);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Resource returned successfully.',
            'data'    => [
                'resource' => $updated,
            ],
        ]);
    }

    /**
     * Resolve permissions for viewing and managing resources for a booking.
     *
     * @param int $userId
     * @param array $booking
     * @return array{can_view: bool, can_manage: bool}
     */
    protected function resolveResourceAuthorization(int $userId, array $booking): array
    {
        if (empty($userId)) {
            return ['can_view' => false, 'can_manage' => false];
        }

        $organizerId = (int) $booking['user_id'];
        $organizer = $this->userModel->find($organizerId);

        $currentUserRecord = $this->userModel->find($userId);
        $currentUserRoleName = (string) (service('session')->get('role_name') ?? '');
        if (empty($currentUserRoleName) && !empty($currentUserRecord['role_id'])) {
            $db = \Config\Database::connect();
            $roleRow = $db->table('roles')->where('id', $currentUserRecord['role_id'])->get()->getRowArray();
            if ($roleRow) {
                $currentUserRoleName = $roleRow['name'];
            }
        }

        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;
        $organizerDeptId   = !empty($organizer['department_id']) ? (int) $organizer['department_id'] : null;

        // 1. Full management: Organizer, Admin, Facilities Manager
        if ($userId === $organizerId || in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true)) {
            return ['can_view' => true, 'can_manage' => true];
        }

        // 2. Full management: Department Manager of the same department
        if ($currentUserRoleName === 'Manager') {
            if ($currentUserDeptId !== null && $organizerDeptId !== null && $currentUserDeptId === $organizerDeptId) {
                return ['can_view' => true, 'can_manage' => true];
            }
        }

        // 3. Read-only: Invited booking participant (response_status != declined)
        $participant = $this->bookingParticipantModel
            ->where('booking_id', (int) $booking['id'])
            ->where('user_id', $userId)
            ->first();

        if ($participant !== null && ($participant['response_status'] ?? '') !== 'declined') {
            return ['can_view' => true, 'can_manage' => false];
        }

        // 4. Denied: Unrelated employee, declined participant, foreign department manager
        return ['can_view' => false, 'can_manage' => false];
    }
}
