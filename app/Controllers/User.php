<?php

namespace App\Controllers;

use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class User extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index(): ResponseInterface
    {
        $users = $this->userModel
            ->select('id, department_id, role_id, first_name, last_name, email, phone, is_active, created_at, updated_at')
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $users,
        ]);
    }
}