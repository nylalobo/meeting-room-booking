<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        return view('dashboard/index', [
            'title' => 'Dashboard',
        ]);
    }

    public function bookings(): string
    {
        return view('bookings/index', [
            'title' => 'Bookings',
        ]);
    }

    public function rooms(): string
    {
        return view('rooms/index', [
            'title' => 'Rooms',
        ]);
    }

    public function locations(): string
    {
        return view('locations/index', [
            'title' => 'Locations',
        ]);
    }

    public function participants(): string
    {
        return view('participants/index', [
            'title' => 'Participants',
        ]);
    }

    public function facilities(): string
    {
        return view('facilities/index', [
            'title' => 'Facilities',
        ]);
    }

    public function equipment(): string
    {
        return view('equipment/index', [
            'title' => 'Equipment',
        ]);
    }

    public function users(): string
    {
        return view('users/index', [
            'title' => 'Users',
        ]);
    }

    public function departments(): string
    {
        return view('departments/index', [
            'title' => 'Departments',
        ]);
    }
}