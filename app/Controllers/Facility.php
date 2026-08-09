<?php

namespace App\Controllers;

use App\Models\Facility as FacilityModel;
use CodeIgniter\HTTP\ResponseInterface;

class Facility extends BaseController
{
    protected FacilityModel $facilityModel;

    public function __construct()
    {
        $this->facilityModel = new FacilityModel();
    }

    public function index(): ResponseInterface
    {
        $facilities = $this->facilityModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $facilities,
        ]);
    }
}