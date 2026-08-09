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

    public function index(): ResponseInterface
    {
        $logs = $this->auditLogModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $logs,
        ]);
    }
}