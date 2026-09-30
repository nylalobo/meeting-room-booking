<?php

namespace App\Controllers;

use App\Models\Location as LocationModel;
use CodeIgniter\HTTP\ResponseInterface;

class Location extends BaseController
{
    protected LocationModel $locationModel;

    public function __construct()
    {
        $this->locationModel = new LocationModel();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /locations
    |--------------------------------------------------------------------------
    | Return all locations.
    */
    public function index(): ResponseInterface
    {
        $all = $this->request->getGet('all') === '1' || $this->request->getGet('all') === 'true' || $this->request->getGet('paginate') === '0';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 10)));
        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');

        $builder = $this->locationModel->builder();

        if ($search !== '') {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('address', $search)
                ->orLike('city', $search)
                ->orLike('state', $search)
                ->orLike('country', $search)
                ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $builder->where('is_active', (int) $status);
        }

        $total = (clone $builder)->countAllResults();

        if ($all) {
            $locations = $builder->orderBy('id', 'ASC')->get()->getResultArray();
            return $this->response->setJSON([
                'status'      => 'success',
                'data'        => $locations,
                'page'        => 1,
                'per_page'    => $total > 0 ? $total : 1,
                'total'       => $total,
                'total_pages' => 1,
            ]);
        }

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $locations = $builder->orderBy('id', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'      => 'success',
            'data'        => $locations,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | GET /locations/{id}
    |--------------------------------------------------------------------------
    | Return one location.
    */
    public function show(int $id): ResponseInterface
    {
        $location = $this->locationModel->find($id);

        if (!$location) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Location not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $location,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | POST /locations
    |--------------------------------------------------------------------------
    | Create a new location.
    */
    public function create(): ResponseInterface
    {
        if (!$this->canManageLocations()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage locations.',
            ]);
        }

        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid JSON request body.',
                ]);
        }

        /*
         * Validate the incoming data before inserting.
         */
        if (!$this->locationModel->validate($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->locationModel->errors(),
                ]);
        }

        /*
         * Default new locations to active when
         * is_active is not supplied.
         */
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        try {
            $locationId = $this->locationModel->insert(
                $data,
                true
            );

            if (!$locationId) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Unable to create location.',
                        'errors'  => $this->locationModel->errors(),
                    ]);
            }

            $location = $this->locationModel->find($locationId);

            return $this->response
                ->setStatusCode(201)
                ->setJSON([
                    'status'  => 'success',
                    'message' => 'Location created successfully.',
                    'data'    => $location,
                ]);

        } catch (\Throwable $e) {
            log_message(
                'error',
                'Location creation failed: ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Unable to create location.',
                ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PUT /locations/{id}
    |--------------------------------------------------------------------------
    | Update an existing location.
    */
    public function update(int $id): ResponseInterface
    {
        if (!$this->canManageLocations()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage locations.',
            ]);
        }

        $location = $this->locationModel->find($id);

        if (!$location) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Location not found.',
                ]);
        }

        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid JSON request body.',
                ]);
        }

        /*
         * Only update fields that are allowed by the model.
         */
        $allowedFields = [
            'name',
            'address',
            'city',
            'state',
            'country',
            'is_active',
        ];

        $updateData = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'No valid fields provided for update.',
                ]);
        }

        /*
         * Validate the updated data.
         *
         * Merge the existing record with the submitted
         * fields so required validation still works.
         */
        $validationData = array_merge(
            $location,
            $updateData
        );

        if (!$this->locationModel->validate($validationData)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->locationModel->errors(),
                ]);
        }

        try {
            $updated = $this->locationModel->update(
                $id,
                $updateData
            );

            if (!$updated) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Unable to update location.',
                        'errors'  => $this->locationModel->errors(),
                    ]);
            }

            $updatedLocation =
                $this->locationModel->find($id);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Location updated successfully.',
                'data'    => $updatedLocation,
            ]);

        } catch (\Throwable $e) {
            log_message(
                'error',
                'Location update failed: ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Unable to update location.',
                ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE /locations/{id}
    |--------------------------------------------------------------------------
    | Delete an existing location.
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | A location may be referenced by rooms.
    | The database foreign key may therefore prevent deletion.
    | If that happens, return a clear error instead of a generic 500.
    */
    public function delete(int $id): ResponseInterface
    {
        if (!$this->canManageLocations()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage locations.',
            ]);
        }

        $location = $this->locationModel->find($id);

        if (!$location) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Location not found.',
                ]);
        }

        try {
            $deleted = $this->locationModel->delete($id);

            if (!$deleted) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Unable to delete location.',
                    ]);
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Location deleted successfully.',
            ]);

        } catch (\Throwable $e) {
            log_message(
                'error',
                'Location deletion failed: ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => 'error',
                    'message' =>
                        'This location cannot be deleted because it may be used by one or more rooms.',
                ]);
        }
    }

    /**
     * RBAC helper matching Equipment approach.
     */
    protected function canManageLocations(): bool
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
}