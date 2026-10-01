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

        $now = date('Y-m-d H:i:s');

        // Fetch up to 5 upcoming or currently active approved meetings
        $rawUpcoming = $this->bookingModel
            ->select('bookings.id, bookings.title, bookings.room_id, bookings.start_time, bookings.end_time, bookings.status, rooms.name as room_name')
            ->join('rooms', 'rooms.id = bookings.room_id', 'left')
            ->where('bookings.end_time >=', $now)
            ->where('bookings.status', 'approved')
            ->orderBy('bookings.start_time', 'ASC')
            ->limit(5)
            ->findAll();

        $upcomingMeetings = [];
        $nowTs = strtotime($now);

        foreach ($rawUpcoming as $b) {
            $startTs  = strtotime($b['start_time']);
            $endTs    = strtotime($b['end_time']);
            $isActive = ($startTs <= $nowTs && $endTs >= $nowTs);

            $upcomingMeetings[] = [
                'id'           => (int) $b['id'],
                'title'        => $b['title'] ?: 'Untitled Meeting',
                'room_name'    => $b['room_name'] ?: 'Room',
                'start_time'   => $b['start_time'],
                'end_time'     => $b['end_time'],
                'date'         => date('M j, Y', $startTs),
                'time_range'   => date('h:i A', $startTs) . ' - ' . date('h:i A', $endTs),
                'status'       => $b['status'],
                'is_active'    => $isActive,
                'status_badge' => $isActive ? 'Active' : 'Upcoming',
                'status_class' => $isActive ? 'badge-active' : 'badge-upcoming',
            ];
        }

        // Fetch up to 3 active rooms and compute their real-time availability
        $activeRooms = $this->roomModel
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->limit(3)
            ->findAll();

        $quickRooms = [];
        foreach ($activeRooms as $rm) {
            $currentBooking = $this->bookingModel
                ->where('room_id', (int) $rm['id'])
                ->where('status', 'approved')
                ->where('start_time <=', $now)
                ->where('end_time >', $now)
                ->first();

            $isAvailable = ($currentBooking === null);
            if ($isAvailable) {
                $statusText  = 'Available Now';
                $statusClass = 'avail-now';
            } else {
                $minsLeft    = max(1, (int) ceil((strtotime($currentBooking['end_time']) - $nowTs) / 60));
                $statusText  = 'In Use (Free in ' . $minsLeft . 'm)';
                $statusClass = 'avail-busy';
            }

            $quickRooms[] = [
                'id'           => (int) $rm['id'],
                'name'         => $rm['name'],
                'capacity'     => (int) $rm['capacity'],
                'floor'        => $rm['floor'] ?? '',
                'is_available' => $isAvailable,
                'status_text'  => $statusText,
                'status_class' => $statusClass,
            ];
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => [
                'total_rooms'       => $totalRooms,
                'total_bookings'    => $totalBookings,
                'pending_requests'  => $pendingRequests,
                'active_users'      => $activeUsers,
                'upcoming_meetings' => $upcomingMeetings,
                'quick_rooms'       => $quickRooms,
            ],
        ]);
    }
}