<?php

namespace App\Controllers;

use App\Models\Role as RoleModel;
use CodeIgniter\HTTP\ResponseInterface;

class Role extends BaseController
{
    protected RoleModel $roleModel;

    public function __construct()
    {
        $this->roleModel = new RoleModel();
    }

    /**
     * Authoritative Admin check.
     */
    protected function isAdminUser(): bool
    {
        $session = service('session');
        $userId = (int) ($session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $roleName = (string) ($session->get('role') ?? '');
        $roleId = (int) ($session->get('role_id') ?? 0);

        if (strcasecmp($roleName, 'Admin') === 0 || $roleId === 1) {
            return true;
        }

        // Authoritative DB verification fallback
        $db = \Config\Database::connect();
        $user = $db->table('users')
                   ->select('users.id, roles.name as role_name')
                   ->join('roles', 'roles.id = users.role_id', 'left')
                   ->where('users.id', $userId)
                   ->get()
                   ->getRowArray();

        if ($user && strcasecmp((string) ($user['role_name'] ?? ''), 'Admin') === 0) {
            $session->set('role', 'Admin');
            return true;
        }

        return false;
    }

    /**
     * Check if incoming request expects JSON.
     */
    protected function isJsonRequest(): bool
    {
        if ($this->request->isAJAX()) {
            return true;
        }

        $acceptHeader = $this->request->getHeaderLine('Accept');
        if (str_contains($acceptHeader, 'application/json')) {
            return true;
        }

        $contentType = $this->request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        return false;
    }

    /**
     * Serve Roles Page (HTML) or Roles List (JSON).
     *
     * GET /roles
     * GET /admin/roles
     * GET /api/roles
     */
    public function index(): ResponseInterface|string
    {
        $session = service('session');
        $userId = (int) ($session->get('user_id') ?? 0);

        // Check if browser navigation request (Accept: text/html and not AJAX)
        $accept = $this->request->getHeaderLine('Accept');
        $isHtmlBrowser = str_contains($accept, 'text/html') && !$this->request->isAJAX();

        if ($isHtmlBrowser) {
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

        $all = $this->request->getGet('all') === '1' || $this->request->getGet('all') === 'true' || $this->request->getGet('paginate') === '0';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 10)));
        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q') ?? ''));

        // Fetch roles with assigned user count
        $db = \Config\Database::connect();

        $countBuilder = $db->table('roles');
        if ($search !== '') {
            $countBuilder->groupStart()
                ->like('roles.name', $search)
                ->orLike('roles.description', $search)
                ->groupEnd();
        }
        $total = $countBuilder->countAllResults();

        $builder = $db->table('roles');
        $builder->select('roles.id, roles.name, roles.description, roles.created_at, roles.updated_at, COUNT(users.id) as user_count')
                ->join('users', 'users.role_id = roles.id', 'left');

        if ($search !== '') {
            $builder->groupStart()
                ->like('roles.name', $search)
                ->orLike('roles.description', $search)
                ->groupEnd();
        }

        $builder->groupBy('roles.id')
                ->orderBy('roles.id', 'ASC');

        if (!$all) {
            $offset = ($page - 1) * $perPage;
            $builder->limit($perPage, $offset);
        }

        $rows = $builder->get()->getResultArray();
        $roles = array_map(static function ($r) {
            $r['id']         = (int) $r['id'];
            $r['user_count'] = (int) ($r['user_count'] ?? 0);
            return $r;
        }, $rows);

        $totalPages = $total > 0 ? (int) ceil($total / ($all ? max(1, $total) : $perPage)) : 1;

        return $this->response->setJSON([
            'status'      => 'success',
            'data'        => $roles,
            'roles'       => $roles,
            'page'        => $all ? 1 : $page,
            'per_page'    => $all ? ($total > 0 ? $total : 1) : $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
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
        $role = $db->table('roles')
                   ->select('roles.id, roles.name, roles.description, roles.created_at, roles.updated_at, COUNT(users.id) as user_count')
                   ->join('users', 'users.role_id = roles.id', 'left')
                   ->where('roles.id', $id)
                   ->groupBy('roles.id')
                   ->get()
                   ->getRowArray();

        if (!$role) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Role not found.',
            ]);
        }

        $role['id'] = (int) $role['id'];
        $role['user_count'] = (int) ($role['user_count'] ?? 0);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $role,
            'role'   => $role,
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

        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));

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

        $input = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));

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

        // Uniqueness check excluding current role
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

        // Protection: System Admin role cannot be deleted
        if ($id === 1) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'The system Administrator role cannot be deleted.',
            ]);
        }

        $db = \Config\Database::connect();
        $role = $db->table('roles')->where('id', $id)->get()->getRowArray();
        if (!$role) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Role not found.',
            ]);
        }

        if (strcasecmp((string) $role['name'], 'Admin') === 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'The system Administrator role cannot be deleted.',
            ]);
        }

        // Protection: Cannot delete role if assigned to any user
        $assignedCount = $db->table('users')->where('role_id', $id)->countAllResults();
        if ($assignedCount > 0) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => "Cannot delete role \"{$role['name']}\" because it is currently assigned to {$assignedCount} user(s). Reassign them first.",
            ]);
        }

        $db->table('roles')->where('id', $id)->delete();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Role \"{$role['name']}\" deleted successfully.",
        ]);
    }
}