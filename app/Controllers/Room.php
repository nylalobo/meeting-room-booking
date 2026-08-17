<?php

namespace App\Controllers;

use App\Models\Room as RoomModel;
use CodeIgniter\HTTP\ResponseInterface;

class Room extends BaseController
{
    protected RoomModel $roomModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
    }

    public function index(): ResponseInterface
    {
        $rooms = $this->roomModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $rooms,
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $room = $this->roomModel->find($id);

        if ($room === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $room,
        ]);
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (!$this->roomModel->insert($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->roomModel->errors(),
                ]);
        }

        $room = $this->roomModel->find(
            $this->roomModel->getInsertID()
        );

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => 'success',
                'message' => 'Room created successfully.',
                'data'    => $room,
            ]);
    }

    public function update(int $id): ResponseInterface
    {
        $room = $this->roomModel->find($id);

        if ($room === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room not found.',
                ]);
        }

        $data = $this->request->getJSON(true);

        if (!$this->roomModel->update($id, $data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->roomModel->errors(),
                ]);
        }

        $updatedRoom = $this->roomModel->find($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Room updated successfully.',
            'data'    => $updatedRoom,
        ]);
    }

    public function delete(int $id): ResponseInterface
    {
        $room = $this->roomModel->find($id);

        if ($room === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room not found.',
                ]);
        }

        if (!$this->roomModel->delete($id)) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to delete room.',
                ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Room deleted successfully.',
        ]);
    }
}