<?php

namespace App\Controllers;

use App\Models\Role as RoleModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Role extends BaseController
{
    protected RoleModel $roleModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
        $this->userModel = new UserModel();
    }

    /**
     * Check if the authenticated user has Administrator privileges.
     */
    protected function isAdminUser(): bool
    {
        $session = service('session');
        $userId  = (int) ($session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $roleId   = (int) ($session->get('role_id') ?? 0);
        $roleName = (string) ($session->get('role_name') ?? '');

        if ($roleId === 1 || strcasecmp($roleName, 'Admin') === 0) {
            return true;
        }

        // Authoritative database check
        $db   = \Config\Database::connect();
        $user = $db->table('users')->where('id', $userId)->get()->getRowArray();
        if ($user && !empty($user['role_id'])) {
            if ((int) $user['role_id'] === 1) {
                return true;
            }
            $role = $db->table('roles')->where('id', $user['role_id'])->get()->getRowArray();
            if ($role && strcasecmp((string) $role['name'], 'Admin') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the incoming request expects a JSON response.
     */
    protected function isJsonRequest(): bool
    {
        return $this->request->isAJAX()
            || str_starts_with($this->request->getUri()->getPath(), 'api/')
            || str_contains((string) $this->request->getHeaderLine('Accept'), 'application/json');
    }

    /**
     * Render the User Roles management view (Admin web) OR list roles as JSON (API/Dropdown).
     *
     * GET /roles
     * GET /admin/roles
     * GET /api/roles
     */
    public function index(): string|ResponseInterface
    {
        $session = service('session');
        $userId  = (int) ($session->get('user_id') ?? 0);

        // If browser page visit (not JSON and accepts text/html)
        if (str_contains((string) $this->request->getHeaderLine('Accept'), 'text/html') && !$this->request->isAJAX()) {
            if ($userId <= 0) {
                return redirect()->to('/login');
            }

            if (!$this->isAdminUser()) {
                $session->setFlashdata('error', 'Access denied. Administrator privileges required to access User Roles.');
                return redirect()->to('/');
            }

            return view('roles/index', [
                'title' => 'User Roles',
            ]);
        }

        // API / JSON request - Requires authentication
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        // Fetch roles with assigned user count
        $db = \Config\Database::connect();
        $builder = $db->table('roles');
        $builder->select('roles.id, roles.name, roles.description, roles.created_at, roles.updated_at, COUNT(users.id) as user_count')
                ->join('users', 'users.role_id = roles.id', 'left')
                ->groupBy('roles.id')
                ->orderBy('roles.id', 'ASC');

        $rows = $builder->get()->getResultArray();
        $roles = array_map(static function ($r) {
            $r['id']         = (int) $r['id'];
            $r['user_count'] = (int) ($r['user_count'] ?? 0);
            return $r;
        }, $rows);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $roles,
            'roles'  => $roles,
        ]);
    }

    /**
     * Show a single role with assigned user count.
     *
     * GET /api/roles/{id}
     */
    public function show(int|string $id): ResponseInterface
    {
        $session = service('session');
        if (empty($session->get('user_id'))) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!$this->isAdminUser()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Administrator privileges required.',
            ]);
        }

        $id = (int) $id;
        $db = \Config\Database::connect();
        $builder = $db->table('roles');
        $builder->select('roles.id, roles.name, roles.description, roles.created_at, roles.updated_at, COUNT(users.id) as user_count')
                ->join('users', 'users.role_id = roles.id', 'left')
                ->where('roles.id', $id)
                ->groupBy('roles.id');

        $role = $builder->get()->getRowArray();
        if (!$role) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Role not found.',
            ]);
        }

        $role['id']         = (int) $role['id'];
        $role['user_count'] = (int) ($role['user_count'] ?? 0);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $role,
        ]);
    }

    /**
     * Create a new role (Admin only).
     *
     * POST /api/roles
     */
    public function create(): ResponseInterface
    {
        $session = service('session');
        if (empty($session->get('user_id'))) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!$this->isAdminUser()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Administrator privileges required.',
            ]);
        }

        // Support both POST form fields and JSON payloads
        $data = $this->request->is('json') ? ($this->request->getJSON(true) ?? []) : $this->request->getPost();

        $name        = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Role name is required.',
                'errors'  => ['name' => 'Role name is required.'],
            ]);
        }

        if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Role name must be between 2 and 50 characters.',
                'errors'  => ['name' => 'Role name must be between 2 and 50 characters.'],
            ]);
        }

        // Check uniqueness (case-insensitive)
        $db = \Config\Database::connect();
        $existing = $db->table('roles')
                       ->where('LOWER(name)', strtolower($name))
                       ->get()
                       ->getRowArray();

        if ($existing) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'A role with this name already exists.',
                'errors'  => ['name' => 'A role with this name already exists.'],
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $insertData = [
            'name'        => $name,
            'description' => $description !== '' ? $description : null,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];

        $db->table('roles')->insert($insertData);
        $newId = (int) $db->insertID();

        $newRole = [
            'id'          => $newId,
            'name'        => $name,
            'description' => $description !== '' ? $description : null,
            'user_count'  => 0,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Role created successfully.',
            'data'    => $newRole,
            'role'    => $newRole,
        ]);
    }

    /**
     * Update an existing role (Admin only).
     *
     * PUT /api/roles/{id}
     */
    public function update(int|string $id): ResponseInterface
    {
        $session = service('session');
        if (empty($session->get('user_id'))) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!$this->isAdminUser()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Administrator privileges required.',
            ]);
        }

        $id = (int) $id;
        $db = \Config\Database::connect();
        $role = $db->table('roles')->where('id', $id)->get()->getRowArray();
        if (!$role) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Role not found.',
            ]);
        }

        $data = $this->request->is('json') ? ($this->request->getJSON(true) ?? []) : $this->request->getRawInput();

        $name        = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        if ($name === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Role name is required.',
                'errors'  => ['name' => 'Role name is required.'],
            ]);
        }

        if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Role name must be between 2 and 50 characters.',
                'errors'  => ['name' => 'Role name must be between 2 and 50 characters.'],
            ]);
        }

        // Check uniqueness excluding current role
        $existing = $db->table('roles')
                       ->where('LOWER(name)', strtolower($name))
                       ->where('id !=', $id)
                       ->get()
                       ->getRowArray();

        if ($existing) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'A role with this name already exists.',
                'errors'  => ['name' => 'A role with this name already exists.'],
            ]);
        }

        // Prevent renaming the core Administrator role
        if ($id === 1 && strcasecmp($name, 'Admin') !== 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'The core system Admin role name cannot be renamed.',
                'errors'  => ['name' => 'The core system Admin role name cannot be renamed.'],
            ]);
        }

        $now = date('Y-m-d H:i:s');
        $updateData = [
            'name'        => $name,
            'description' => $description !== '' ? $description : null,
            'updated_at'  => $now,
        ];

        $db->table('roles')->where('id', $id)->update($updateData);

        $userCount = $db->table('users')->where('role_id', $id)->countAllResults();

        $updatedRole = [
            'id'          => $id,
            'name'        => $name,
            'description' => $description !== '' ? $description : null,
            'user_count'  => (int) $userCount,
            'created_at'  => $role['created_at'],
            'updated_at'  => $now,
        ];

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Role updated successfully.',
            'data'    => $updatedRole,
            'role'    => $updatedRole,
        ]);
    }

    /**
     * Delete an unused role (Admin only).
     *
     * DELETE /api/roles/{id}
     */
    public function delete(int|string $id): ResponseInterface
    {
        $session = service('session');
        if (empty($session->get('user_id'))) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!$this->isAdminUser()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Administrator privileges required.',
            ]);
        }

        $id = (int) $id;
        $db = \Config\Database::connect();
        $role = $db->table('roles')->where('id', $id)->get()->getRowArray();
        if (!$role) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Role not found.',
            ]);
        }

        // Prevent deletion of primary Admin role
        if ($id === 1 || strcasecmp($role['name'], 'Admin') === 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'The system Administrator role cannot be deleted.',
            ]);
        }

        // Check if assigned to any user
        $userCount = $db->table('users')->where('role_id', $id)->countAllResults();
        if ($userCount > 0) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => "Cannot delete role \"{$role['name']}\" because it is currently assigned to {$userCount} user(s). Reassign them first.",
            ]);
        }

        $db->table('roles')->where('id', $id)->delete();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Role \"{$role['name']}\" deleted successfully.",
        ]);
    }
}