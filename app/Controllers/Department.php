<?php

namespace App\Controllers;

use App\Models\Department as DepartmentModel;
use CodeIgniter\HTTP\ResponseInterface;

class Department extends BaseController
{
    protected DepartmentModel $departmentModel;

    public function __construct()
    {
        $this->departmentModel = new DepartmentModel();
    }

    public function index(): ResponseInterface
    {
        $departments = $this->departmentModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $departments,
        ]);
    }
}