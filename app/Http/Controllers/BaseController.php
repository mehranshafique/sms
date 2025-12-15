<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BaseController extends LaravelController
{
    use AuthorizesRequests;
    public function __construct() {

    }
    protected $pageTitle = null;

    public function setPageTitle($title)
    {
        $this->pageTitle = $title;

        view()->share('pageTitle', $title);
    }
}
