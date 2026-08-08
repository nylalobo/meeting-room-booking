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

    public function index(): ResponseInterface
    {
        $roles = $this->roleModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $roles,
        ]);
    }
}