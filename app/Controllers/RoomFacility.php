<?php

namespace App\Controllers;

use App\Models\RoomFacility as RoomFacilityModel;
use CodeIgniter\HTTP\ResponseInterface;

class RoomFacility extends BaseController
{
    protected RoomFacilityModel $roomFacilityModel;

    public function __construct()
    {
        $this->roomFacilityModel = new RoomFacilityModel();
    }

    public function index(): ResponseInterface
    {
        $roomFacilities = $this->roomFacilityModel
            ->orderBy('room_id', 'ASC')
            ->orderBy('facility_id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $roomFacilities,
        ]);
    }
}