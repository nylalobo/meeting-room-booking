<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

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

    public function users(): ResponseInterface|string
    {
        $session = service('session');
        $userId = (int) ($session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return redirect()->to('/login');
        }

        $roleName = (string) ($session->get('role_name') ?? $session->get('role') ?? '');
        $roleId = (int) ($session->get('role_id') ?? 0);

        $isAdmin = ($roleId === 1 || strcasecmp($roleName, 'Admin') === 0);
        if (!$isAdmin) {
            $db = \Config\Database::connect();
            $user = $db->table('users')
                       ->select('users.id, roles.name as role_name')
                       ->join('roles', 'roles.id = users.role_id', 'left')
                       ->where('users.id', $userId)
                       ->get()
                       ->getRowArray();
            if ($user && strcasecmp((string) ($user['role_name'] ?? ''), 'Admin') === 0) {
                $session->set('role', 'Admin');
                $session->set('role_name', 'Admin');
                $isAdmin = true;
            }
        }

        if (!$isAdmin) {
            $session->setFlashdata('error', 'Access denied. Administrator privileges required to access Users management.');
            return redirect()->to('/');
        }

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