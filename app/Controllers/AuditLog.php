<?php

namespace App\Controllers;

use App\Models\AuditLog as AuditLogModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuditLog extends BaseController
{
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->auditLogModel = new AuditLogModel();
    }

    protected function isAdminUser(): bool
    {
        $session = service('session');
        $userId = (int) ($session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $roleName = (string) ($session->get('role_name') ?? $session->get('role') ?? '');
        $roleId = (int) ($session->get('role_id') ?? 0);

        if (strcasecmp($roleName, 'Admin') === 0 || $roleId === 1) {
            return true;
        }

        $db = \Config\Database::connect();
        $user = $db->table('users')
                   ->select('users.id, roles.name as role_name')
                   ->join('roles', 'roles.id = users.role_id', 'left')
                   ->where('users.id', $userId)
                   ->get()
                   ->getRowArray();

        if ($user && strcasecmp((string) ($user['role_name'] ?? ''), 'Admin') === 0) {
            $session->set('role', 'Admin');
            $session->set('role_name', 'Admin');
            return true;
        }

        return false;
    }

    public function index(): ResponseInterface
    {
        if (!$this->isAdminUser()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Administrator privileges required to access audit logs.',
            ]);
        }

        $logs = $this->auditLogModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $logs,
        ]);
    }
}