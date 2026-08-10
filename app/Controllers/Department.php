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

    public function show(int $id): ResponseInterface
    {
        $department = $this->departmentModel->find($id);

        if ($department === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Department not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $department,
        ]);
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (!$this->departmentModel->insert($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->departmentModel->errors(),
                ]);
        }

        $department = $this->departmentModel->find(
            $this->departmentModel->getInsertID()
        );

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => 'success',
                'message' => 'Department created successfully.',
                'data'    => $department,
            ]);
    }

    public function update(int $id): ResponseInterface
    {
        $department = $this->departmentModel->find($id);

        if ($department === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Department not found.',
                ]);
        }

        $data = $this->request->getJSON(true);

        if (!$this->departmentModel->update($id, $data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->departmentModel->errors(),
                ]);
        }

        $updatedDepartment = $this->departmentModel->find($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Department updated successfully.',
            'data'    => $updatedDepartment,
        ]);
    }
    public function delete(int $id): ResponseInterface
{
    $department = $this->departmentModel->find($id);

    if ($department === null) {
        return $this->response
            ->setStatusCode(404)
            ->setJSON([
                'status'  => 'error',
                'message' => 'Department not found.',
            ]);
    }

    if (!$this->departmentModel->delete($id)) {
        return $this->response
            ->setStatusCode(500)
            ->setJSON([
                'status'  => 'error',
                'message' => 'Failed to delete department.',
            ]);
    }

    return $this->response->setJSON([
        'status'  => 'success',
        'message' => 'Department deleted successfully.',
    ]);
    }
}