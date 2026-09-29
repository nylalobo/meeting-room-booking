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
        $all = $this->request->getGet('all') === '1' || $this->request->getGet('all') === 'true' || $this->request->getGet('paginate') === '0';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 10)));
        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q') ?? ''));
        $bookingId = $this->request->getGet('booking_id');
        $userId = $this->request->getGet('user_id');
        $type = $this->request->getGet('participant_type') ?? $this->request->getGet('type');
        $status = $this->request->getGet('response_status') ?? $this->request->getGet('status');

        $builder = $this->bookingParticipantModel->builder();
        $builder->select('booking_participants.*');
        $builder->join('users', 'users.id = booking_participants.user_id', 'left');
        $builder->join('bookings', 'bookings.id = booking_participants.booking_id', 'left');

        if ($search !== '') {
            $builder->groupStart()
                ->like('users.first_name', $search)
                ->orLike('users.last_name', $search)
                ->orLike('users.email', $search)
                ->orLike('bookings.title', $search)
                ->groupEnd();
        }

        if ($bookingId !== null && $bookingId !== '') {
            $builder->where('booking_participants.booking_id', (int) $bookingId);
        }

        if ($userId !== null && $userId !== '') {
            $builder->where('booking_participants.user_id', (int) $userId);
        }

        if ($type !== null && $type !== '') {
            $builder->where('booking_participants.participant_type', strtolower(trim((string) $type)));
        }

        if ($status !== null && $status !== '') {
            $builder->where('booking_participants.response_status', strtolower(trim((string) $status)));
        }

        $total = (clone $builder)->countAllResults();

        if ($all) {
            $participants = $builder->orderBy('booking_participants.booking_id', 'ASC')->get()->getResultArray();
            return $this->response->setJSON([
                'status'      => 'success',
                'data'        => $participants,
                'page'        => 1,
                'per_page'    => $total > 0 ? $total : 1,
                'total'       => $total,
                'total_pages' => 1,
            ]);
        }

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $participants = $builder->orderBy('booking_participants.booking_id', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'      => 'success',
            'data'        => $participants,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
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