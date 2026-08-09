<?php

namespace App\Controllers;

use App\Models\Booking as BookingModel;
use CodeIgniter\HTTP\ResponseInterface;

class Booking extends BaseController
{
    protected BookingModel $bookingModel;

    public function __construct()
    {
        $this->bookingModel = new BookingModel();
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
}
