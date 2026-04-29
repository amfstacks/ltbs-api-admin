<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseController
{
    public function index()
    {
        // Later, we will query the database here to get real metrics.
        // For now, we pass the title to our DRY master layout.
        $data = [
            'title' => 'Dashboard Overview'
        ];

        return view('admin/dashboard/index', $data);
    }
}
