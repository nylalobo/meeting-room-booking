<?php

namespace App\Controllers;

use App\Models\Location as LocationModel;
use CodeIgniter\HTTP\ResponseInterface;

class Location extends BaseController
{
    protected LocationModel $locationModel;

    public function __construct()
    {
        $this->locationModel = new LocationModel();
    }

    public function index(): ResponseInterface
    {
        $locations = $this->locationModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $locations,
        ]);
    }
}