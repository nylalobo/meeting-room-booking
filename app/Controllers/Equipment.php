<?php

namespace App\Controllers;

use App\Models\AuditLog as AuditLogModel;
use App\Models\Equipment as EquipmentModel;
use App\Models\Location as LocationModel;
use App\Models\Role as RoleModel;
use App\Models\Room as RoomModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Equipment extends BaseController
{
    protected EquipmentModel $equipmentModel;
    protected LocationModel $locationModel;
    protected RoomModel $roomModel;
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->equipmentModel = new EquipmentModel();
        $this->locationModel  = new LocationModel();
        $this->roomModel      = new RoomModel();
        $this->userModel      = new UserModel();
        $this->roleModel      = new RoleModel();
        $this->auditLogModel  = new AuditLogModel();
    }

    /**
     * List equipment catalog records with optional filters.
     *
     * GET /api/equipment
     */
    public function index(): ResponseInterface
    {
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $all = $this->request->getGet('all') === '1' || $this->request->getGet('all') === 'true' || $this->request->getGet('paginate') === '0';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 10)));

        $filters = [
            'status'          => $this->request->getGet('status'),
            'category'        => $this->request->getGet('category'),
            'location_id'     => $this->request->getGet('location_id'),
            'default_room_id' => $this->request->getGet('default_room_id'),
            'search'          => $this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q'),
        ];

        $total = $this->equipmentModel->countEquipmentWithDetails($filters);

        if ($all) {
            $equipment = $this->equipmentModel->getEquipmentWithDetails(null, $filters);
            return $this->response->setJSON([
                'status'      => 'success',
                'data'        => $equipment,
                'page'        => 1,
                'per_page'    => $total > 0 ? $total : 1,
                'total'       => $total,
                'total_pages' => 1,
            ]);
        }

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $equipment = $this->equipmentModel->getEquipmentWithDetails(null, $filters, $perPage, $offset);

        return $this->response->setJSON([
            'status'      => 'success',
            'data'        => $equipment,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
        ]);
    }

    /**
     * Get available equipment eligible for new assignment.
     *
     * GET /api/equipment/availability
     */
    public function availability(): ResponseInterface
    {
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $filters = [
            'status'          => 'available', // Permanent catalog available only
            'category'        => $this->request->getGet('category'),
            'location_id'     => $this->request->getGet('location_id'),
            'default_room_id' => $this->request->getGet('default_room_id'),
            'search'          => $this->request->getGet('search'),
        ];

        $equipment = $this->equipmentModel->getEquipmentWithDetails(null, $filters);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $equipment,
        ]);
    }

    /**
     * Get a single equipment item by ID.
     *
     * GET /api/equipment/{id}
     */
    public function show(int $id): ResponseInterface
    {
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $equipment = $this->equipmentModel->getEquipmentWithDetails($id);

        if (empty($equipment)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Equipment not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $equipment,
        ]);
    }

    /**
     * Create a new equipment item.
     *
     * POST /api/equipment
     */
    public function create(): ResponseInterface
    {
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!$this->canManageEquipment($currentUserId)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage equipment.',
            ]);
        }

        $data = $this->request->getJSON(true) ?? [];

        // Required field validation check
        $errors = [];
        if (empty($data['name']) || trim((string) $data['name']) === '') {
            $errors['name'] = 'Equipment name is required.';
        }
        if (empty($data['code']) || trim((string) $data['code']) === '') {
            $errors['code'] = 'Equipment code is required.';
        }
        if (empty($data['category']) || trim((string) $data['category']) === '') {
            $errors['category'] = 'Equipment category is required.';
        }

        // Validate foreign keys when supplied
        if (!empty($data['location_id'])) {
            $loc = $this->locationModel->find((int) $data['location_id']);
            if ($loc === null) {
                $errors['location_id'] = 'The specified location does not exist.';
            }
        }

        if (!empty($data['default_room_id'])) {
            $rm = $this->roomModel->find((int) $data['default_room_id']);
            if ($rm === null) {
                $errors['default_room_id'] = 'The specified room does not exist.';
            }
        }

        if (!empty($errors)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Validation failed.',
                    'errors'  => $errors,
                ]);
        }

        // Default status to 'available' if omitted
        if (empty($data['status'])) {
            $data['status'] = 'available';
        }

        if (!$this->equipmentModel->insert($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Validation failed.',
                    'errors'  => $this->equipmentModel->errors(),
                ]);
        }

        $newId   = (int) $this->equipmentModel->getInsertID();
        $created = $this->equipmentModel->getEquipmentWithDetails($newId);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'equipment_created',
            'table_name' => 'equipment',
            'record_id'  => $newId,
            'old_values' => null,
            'new_values' => json_encode($created),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => 'success',
                'message' => 'Equipment created successfully.',
                'data'    => $created,
            ]);
    }

    /**
     * Update an existing equipment item.
     *
     * PUT /api/equipment/{id}
     */
    public function update(int $id): ResponseInterface
    {
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!$this->canManageEquipment($currentUserId)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage equipment.',
            ]);
        }

        $existing = $this->equipmentModel->find($id);
        if ($existing === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Equipment not found.',
                ]);
        }

        $data = $this->request->getJSON(true) ?? [];

        // Validate foreign keys when supplied
        $errors = [];
        if (array_key_exists('location_id', $data) && !empty($data['location_id'])) {
            $loc = $this->locationModel->find((int) $data['location_id']);
            if ($loc === null) {
                $errors['location_id'] = 'The specified location does not exist.';
            }
        }

        if (array_key_exists('default_room_id', $data) && !empty($data['default_room_id'])) {
            $rm = $this->roomModel->find((int) $data['default_room_id']);
            if ($rm === null) {
                $errors['default_room_id'] = 'The specified room does not exist.';
            }
        }

        if (!empty($errors)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Validation failed.',
                    'errors'  => $errors,
                ]);
        }

        $data['id'] = $id;

        // Dynamic update uniqueness rules ignoring current record ID
        $this->equipmentModel->setValidationRule(
            'code',
            "required|min_length[2]|max_length[50]|is_unique[equipment.code,id,{$id}]"
        );
        $this->equipmentModel->setValidationRule(
            'serial_number',
            "permit_empty|max_length[100]|is_unique[equipment.serial_number,id,{$id}]"
        );

        if (!$this->equipmentModel->update($id, $data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Validation failed.',
                    'errors'  => $this->equipmentModel->errors(),
                ]);
        }

        $updated = $this->equipmentModel->getEquipmentWithDetails($id);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'equipment_updated',
            'table_name' => 'equipment',
            'record_id'  => $id,
            'old_values' => json_encode($existing),
            'new_values' => json_encode($updated),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Equipment updated successfully.',
            'data'    => $updated,
        ]);
    }

    /**
     * Retire an equipment item (Safe catalog retirement, non-destructive).
     *
     * DELETE /api/equipment/{id}
     */
    public function delete(int $id): ResponseInterface
    {
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!$this->canManageEquipment($currentUserId)) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage equipment.',
            ]);
        }

        $existing = $this->equipmentModel->find($id);
        if ($existing === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Equipment not found.',
                ]);
        }

        $oldStatus = $existing['status'];

        // Safe catalog retire operation
        if (!$this->equipmentModel->update($id, ['status' => 'retired'])) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to retire equipment.',
                ]);
        }

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'equipment_retired',
            'table_name' => 'equipment',
            'record_id'  => $id,
            'old_values' => json_encode(['status' => $oldStatus]),
            'new_values' => json_encode(['status' => 'retired']),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Equipment retired successfully.',
        ]);
    }

    /**
     * Helper: Get current authenticated user ID from session.
     */
    protected function getCurrentUserId(): int
    {
        $session = service('session');
        return (int) ($session->get('user_id') ?? 0);
    }

    /**
     * Helper: Check whether user has Admin or Facilities Manager privileges.
     */
    protected function canManageEquipment(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $session             = service('session');
        $currentUserRoleName = (string) ($session->get('role_name') ?? '');
        $currentUserRoleId   = (int) ($session->get('role_id') ?? 0);

        if (empty($currentUserRoleName) || $currentUserRoleId <= 0) {
            $user = $this->userModel->find($userId);
            if ($user && !empty($user['role_id'])) {
                $currentUserRoleId = (int) $user['role_id'];
                $role = $this->roleModel->find($currentUserRoleId);
                if ($role) {
                    $currentUserRoleName = (string) $role['name'];
                }
            }
        }

        if ($currentUserRoleId === 1 || $currentUserRoleId === 6) {
            return true;
        }

        return in_array($currentUserRoleName, ['Admin', 'Facilities Manager', 'Facility Manager'], true);
    }
}
