<?php

namespace App\Controllers;

use App\Models\CateringRequest as CateringRequestModel;
use CodeIgniter\HTTP\ResponseInterface;

class CateringRequest extends BaseController
{
    protected CateringRequestModel $cateringRequestModel;

    public function __construct()
    {
        $this->cateringRequestModel = new CateringRequestModel();
    }

    public function index(): ResponseInterface
    {
        $requests = $this->cateringRequestModel
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $requests,
        ]);
    }
}