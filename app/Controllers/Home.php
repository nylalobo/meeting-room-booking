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
}