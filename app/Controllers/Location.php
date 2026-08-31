<?php

namespace App\Controllers;

use App\Models\Location as LocationModel;
use CodeIgniter\HTTP\ResponseInterface;

class Location extends BaseController
{
    protected LocationModel $locationModel;

    public function __construct()
    {
        $this->locationModel = new LocationModel();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /locations
    |--------------------------------------------------------------------------
    | Return all locations.
    */
    public function index(): ResponseInterface
    {
        $locations = $this->locationModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $locations,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | GET /locations/{id}
    |--------------------------------------------------------------------------
    | Return one location.
    */
    public function show(int $id): ResponseInterface
    {
        $location = $this->locationModel->find($id);

        if (!$location) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Location not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $location,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | POST /locations
    |--------------------------------------------------------------------------
    | Create a new location.
    */
    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid JSON request body.',
                ]);
        }

        /*
         * Validate the incoming data before inserting.
         */
        if (!$this->locationModel->validate($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->locationModel->errors(),
                ]);
        }

        /*
         * Default new locations to active when
         * is_active is not supplied.
         */
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        try {
            $locationId = $this->locationModel->insert(
                $data,
                true
            );

            if (!$locationId) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Unable to create location.',
                        'errors'  => $this->locationModel->errors(),
                    ]);
            }

            $location = $this->locationModel->find($locationId);

            return $this->response
                ->setStatusCode(201)
                ->setJSON([
                    'status'  => 'success',
                    'message' => 'Location created successfully.',
                    'data'    => $location,
                ]);

        } catch (\Throwable $e) {
            log_message(
                'error',
                'Location creation failed: ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Unable to create location.',
                ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | PUT /locations/{id}
    |--------------------------------------------------------------------------
    | Update an existing location.
    */
    public function update(int $id): ResponseInterface
    {
        $location = $this->locationModel->find($id);

        if (!$location) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Location not found.',
                ]);
        }

        $data = $this->request->getJSON(true);

        if (!is_array($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid JSON request body.',
                ]);
        }

        /*
         * Only update fields that are allowed by the model.
         */
        $allowedFields = [
            'name',
            'address',
            'city',
            'state',
            'country',
            'is_active',
        ];

        $updateData = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'No valid fields provided for update.',
                ]);
        }

        /*
         * Validate the updated data.
         *
         * Merge the existing record with the submitted
         * fields so required validation still works.
         */
        $validationData = array_merge(
            $location,
            $updateData
        );

        if (!$this->locationModel->validate($validationData)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->locationModel->errors(),
                ]);
        }

        try {
            $updated = $this->locationModel->update(
                $id,
                $updateData
            );

            if (!$updated) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Unable to update location.',
                        'errors'  => $this->locationModel->errors(),
                    ]);
            }

            $updatedLocation =
                $this->locationModel->find($id);

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Location updated successfully.',
                'data'    => $updatedLocation,
            ]);

        } catch (\Throwable $e) {
            log_message(
                'error',
                'Location update failed: ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Unable to update location.',
                ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE /locations/{id}
    |--------------------------------------------------------------------------
    | Delete an existing location.
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    | A location may be referenced by rooms.
    | The database foreign key may therefore prevent deletion.
    | If that happens, return a clear error instead of a generic 500.
    */
    public function delete(int $id): ResponseInterface
    {
        $location = $this->locationModel->find($id);

        if (!$location) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Location not found.',
                ]);
        }

        try {
            $deleted = $this->locationModel->delete($id);

            if (!$deleted) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Unable to delete location.',
                    ]);
            }

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Location deleted successfully.',
            ]);

        } catch (\Throwable $e) {
            log_message(
                'error',
                'Location deletion failed: ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => 'error',
                    'message' =>
                        'This location cannot be deleted because it may be used by one or more rooms.',
                ]);
        }
    }
}