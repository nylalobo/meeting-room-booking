<?php

namespace App\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use CodeIgniter\HTTP\ResponseInterface;

class Dashboard extends BaseController
{
    protected Room $roomModel;
    protected Booking $bookingModel;
    protected User $userModel;

    public function __construct()
    {
        $this->roomModel = new Room();
        $this->bookingModel = new Booking();
        $this->userModel = new User();
    }

    public function stats(): ResponseInterface
    {
        $totalRooms = $this->roomModel->countAllResults();

        $totalBookings = $this->bookingModel->countAllResults();

        $pendingRequests = $this->bookingModel
            ->where('status', 'pending')
            ->countAllResults();

        $activeUsers = $this->userModel
            ->where('is_active', 1)
            ->countAllResults();

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'total_rooms' => $totalRooms,
                'total_bookings' => $totalBookings,
                'pending_requests' => $pendingRequests,
                'active_users' => $activeUsers,
            ],
        ]);
    }
}