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
        $facilities = $this->facilityModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $facilities,
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

    public function create(): ResponseInterface
    {
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