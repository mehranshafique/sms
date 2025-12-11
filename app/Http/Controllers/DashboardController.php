<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\BaseController;
class DashboardController extends BaseController
{
    public function index(){
        $this->setPageTitle(__('dashboard.page_title'));
        return view('dashboard.dashboard');
    }
}
