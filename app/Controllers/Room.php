<?php

namespace App\Controllers;

use App\Models\Room as RoomModel;
use CodeIgniter\HTTP\ResponseInterface;

class Room extends BaseController
{
    protected RoomModel $roomModel;

    public function __construct()
    {
        $this->roomModel = new RoomModel();
    }

    public function index(): ResponseInterface
    {
        $rooms = $this->roomModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $rooms,
        ]);
    }
}