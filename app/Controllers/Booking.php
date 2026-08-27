<?php

namespace App\Controllers;

use App\Models\Booking as BookingModel;
use App\Models\Room as RoomModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Booking extends BaseController
{
    protected BookingModel $bookingModel;
    protected RoomModel $roomModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
        $this->roomModel    = new RoomModel();
        $this->userModel    = new UserModel();
    }

    public function index(): ResponseInterface
    {
        $bookings = $this->bookingModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $bookings,
        ]);
    }

    public function show(int $id): ResponseInterface
    {
        $booking = $this->bookingModel->find($id);

        if ($booking === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $booking,
        ]);
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];

        if (!isset($data['status'])) {
            $data['status'] = 'pending';
        }

        $validation = service('validation');
        $validation->setRules(
            $this->bookingModel->getValidationRules(),
            $this->bookingModel->getValidationMessages()
        );

        if (!$validation->run($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $validation->getErrors(),
                ]);
        }

        if (strtotime($data['end_time']) <= strtotime($data['start_time'])) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => [
                        'end_time' => 'End time must be after start time.',
                    ],
                ]);
        }

        $room = $this->roomModel->find($data['room_id']);
        if ($room === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room not found.',
                ]);
        }

        $user = $this->userModel->find($data['user_id']);
        if ($user === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'User not found.',
                ]);
        }

        if (
            $this->bookingModel->hasOverlap(
                (int) $data['room_id'],
                $data['start_time'],
                $data['end_time']
            )
        ) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Room is already booked during this time.',
                ]);
        }

        if (!$this->bookingModel->insert($data, false)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->bookingModel->errors(),
                ]);
        }

        $booking = $this->bookingModel->find(
            $this->bookingModel->getInsertID()
        );

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => 'success',
                'message' => 'Booking created successfully.',
                'data'    => $booking,
            ]);
    }

    public function update(int $id): ResponseInterface
    {
        $booking = $this->bookingModel->find($id);

        if ($booking === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking not found.',
                ]);
        }

        $data = $this->request->getJSON(true);

        $startTime = $data['start_time'] ?? $booking['start_time'];
        $endTime   = $data['end_time'] ?? $booking['end_time'];
        $roomId    = (int) ($data['room_id'] ?? $booking['room_id']);

        if (strtotime($endTime) <= strtotime($startTime)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => [
                        'end_time' => 'End time must be after start time.',
                    ],
                ]);
        }

        if (
            $this->bookingModel->hasOverlap(
                $roomId,
                $startTime,
                $endTime,
                $id
            )
        ) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Room is already booked during this time.',
                ]);
        }

        if (!$this->bookingModel->update($id, $data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->bookingModel->errors(),
                ]);
        }

        $updatedBooking = $this->bookingModel->find($id);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Booking updated successfully.',
            'data'    => $updatedBooking,
        ]);
    }

    public function delete(int $id): ResponseInterface
    {
        $booking = $this->bookingModel->find($id);

        if ($booking === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking not found.',
                ]);
        }

        if (!$this->bookingModel->delete($id)) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to delete booking.',
                ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Booking deleted successfully.',
        ]);
    }
}