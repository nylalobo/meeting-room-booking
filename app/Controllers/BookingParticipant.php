<?php

namespace App\Controllers;

use App\Models\BookingParticipant as BookingParticipantModel;
use App\Models\Booking as BookingModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class BookingParticipant extends BaseController
{
    protected BookingParticipantModel $bookingParticipantModel;
    protected BookingModel $bookingModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->bookingParticipantModel = new BookingParticipantModel();
        $this->bookingModel = new BookingModel();
        $this->userModel = new UserModel();
    }

    public function index(): ResponseInterface
    {
        $participants = $this->bookingParticipantModel
            ->orderBy('booking_id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $participants,
        ]);
    }

    public function show(int $bookingId, int $userId): ResponseInterface
    {
        $participant = $this->bookingParticipantModel
            ->where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->first();

        if ($participant === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking participant not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $participant,
        ]);
    }

    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true);

        if (!isset($data['booking_id']) || !isset($data['user_id'])) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'message' => 'Booking ID and User ID are required.',
                ]);
        }

        $booking = $this->bookingModel->find($data['booking_id']);

        if ($booking === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking not found.',
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

        $existingParticipant = $this->bookingParticipantModel
            ->where('booking_id', $data['booking_id'])
            ->where('user_id', $data['user_id'])
            ->first();

        if ($existingParticipant !== null) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'User is already a participant in this booking.',
                ]);
        }

        if (!isset($data['participant_type'])) {
            $data['participant_type'] = 'participant';
        }

        if (!isset($data['response_status'])) {
            $data['response_status'] = 'pending';
        }

        if (!$this->bookingParticipantModel->insert($data)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $this->bookingParticipantModel->errors(),
                ]);
        }

        $participant = $this->bookingParticipantModel
            ->where('booking_id', $data['booking_id'])
            ->where('user_id', $data['user_id'])
            ->first();

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => 'success',
                'message' => 'Booking participant created successfully.',
                'data'    => $participant,
            ]);
    }

    public function update(int $bookingId, int $userId): ResponseInterface
    {
        $participant = $this->bookingParticipantModel
            ->where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->first();

        if ($participant === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking participant not found.',
                ]);
        }

        $data = $this->request->getJSON(true) ?? [];

        unset($data['booking_id'], $data['user_id']);

        $rules = [];
        $messages = [];

        if (array_key_exists('participant_type', $data)) {
            $rules['participant_type'] = 'required|in_list[organizer,participant,guest]';
            $messages['participant_type'] = [
                'required' => 'Participant type is required.',
                'in_list'  => 'Invalid participant type.',
            ];
        }

        if (array_key_exists('response_status', $data)) {
            $rules['response_status'] = 'required|in_list[pending,accepted,declined,tentative]';
            $messages['response_status'] = [
                'required' => 'Response status is required.',
                'in_list'  => 'Invalid response status.',
            ];
        }

        if (!empty($rules)) {
            $validation = service('validation');
            $validation->setRules($rules, $messages);

            if (!$validation->run($data)) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status' => 'error',
                        'errors' => $validation->getErrors(),
                    ]);
            }
        }

        if (!empty($data)) {
            $this->bookingParticipantModel
                ->where('booking_id', $bookingId)
                ->where('user_id', $userId)
                ->set($data)
                ->update();
        }

        $updatedParticipant = $this->bookingParticipantModel
            ->where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->first();

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Booking participant updated successfully.',
            'data'    => $updatedParticipant,
        ]);
    }

    public function delete(int $bookingId, int $userId): ResponseInterface
    {
        $participant = $this->bookingParticipantModel
            ->where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->first();

        if ($participant === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Booking participant not found.',
                ]);
        }

        $deleted = $this->bookingParticipantModel
            ->where('booking_id', $bookingId)
            ->where('user_id', $userId)
            ->delete();

        if (!$deleted) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to delete booking participant.',
                ]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Booking participant deleted successfully.',
        ]);
    }
}