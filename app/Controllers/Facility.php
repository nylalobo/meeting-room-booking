<?php

namespace App\Controllers;

use App\Models\Facility as FacilityModel;
use CodeIgniter\HTTP\ResponseInterface;

class Facility extends BaseController
{
    protected FacilityModel $facilityModel;

    public function __construct()
    {
        $this->facilityModel = new FacilityModel();
    }

    public function index(): ResponseInterface
    {
        $all = $this->request->getGet('all') === '1' || $this->request->getGet('all') === 'true' || $this->request->getGet('paginate') === '0';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 10)));
        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q') ?? ''));
        $status = $this->request->getGet('status');

        $builder = $this->facilityModel->builder();

        if ($search !== '') {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        if ($status !== null && $status !== '') {
            $builder->where('is_active', (int) $status);
        }

        $total = (clone $builder)->countAllResults();

        if ($all) {
            $facilities = $builder->orderBy('id', 'ASC')->get()->getResultArray();
            return $this->response->setJSON([
                'status'      => 'success',
                'data'        => $facilities,
                'page'        => 1,
                'per_page'    => $total > 0 ? $total : 1,
                'total'       => $total,
                'total_pages' => 1,
            ]);
        }

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $facilities = $builder->orderBy('id', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'      => 'success',
            'data'        => $facilities,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
        ]);
    }


    public function show(int $id): ResponseInterface
    {
        $facility = $this->facilityModel->find($id);

        if ($facility === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Facility not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $facility,
        ]);
    }

    /**
     * RBAC helper matching Equipment approach.
     */
    protected function canManageFacilities(): bool
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
        if (!$this->canManageFacilities()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage facilities.',
            ]);
        }

        $data = $this->request->getJSON(true);

        if (!$this->facilityModel->insert($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->facilityModel->errors(),
                ]);
        }

        $facility = $this->facilityModel->find(
            $this->facilityModel->getInsertID()
        );

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => 'success',
                'message' => 'Facility created successfully.',
                'data'    => $facility,
            ]);
    }

    public function update(int $id): ResponseInterface
    {
        if (!$this->canManageFacilities()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage facilities.',
            ]);
        }

        $facility = $this->facilityModel->find($id);

        if ($facility === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Facility not found.',
                ]);
        }

        $data = $this->request->getJSON(true);

        if (!$this->facilityModel->update($id, $data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->facilityModel->errors(),
                ]);
        }

        $updatedFacility = $this->facilityModel->find($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Facility updated successfully.',
            'data'    => $updatedFacility,
        ]);
    }

    public function delete(int $id): ResponseInterface
    {
        if (!$this->canManageFacilities()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to manage facilities.',
            ]);
        }

        $facility = $this->facilityModel->find($id);

        if ($facility === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Facility not found.',
                ]);
        }

        if (!$this->facilityModel->delete($id)) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to delete facility.',
                ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Facility deleted successfully.',
        ]);
    }
}