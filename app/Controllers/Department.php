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
        $all = $this->request->getGet('all') === '1' || $this->request->getGet('all') === 'true' || $this->request->getGet('paginate') === '0';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 10)));
        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q') ?? ''));

        $builder = $this->departmentModel->builder();

        if ($search !== '') {
            $builder->groupStart()
                ->like('name', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        $total = (clone $builder)->countAllResults();

        if ($all) {
            $departments = $builder->orderBy('id', 'ASC')->get()->getResultArray();
            return $this->response->setJSON([
                'status'      => 'success',
                'data'        => $departments,
                'page'        => 1,
                'per_page'    => $total > 0 ? $total : 1,
                'total'       => $total,
                'total_pages' => 1,
            ]);
        }

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $departments = $builder->orderBy('id', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'      => 'success',
            'data'        => $departments,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
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