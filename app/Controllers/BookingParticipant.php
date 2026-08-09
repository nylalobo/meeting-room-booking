<?php

namespace App\Controllers;

use App\Models\BookingParticipant as BookingParticipantModel;
use CodeIgniter\HTTP\ResponseInterface;

class BookingParticipant extends BaseController
{
    protected BookingParticipantModel $bookingParticipantModel;

    public function __construct()
    {
        $this->bookingParticipantModel = new BookingParticipantModel();
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
}