<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as LaravelController;

class BaseController extends LaravelController
{
    public function __construct() {

    }
    protected $pageTitle = null;

    public function setPageTitle($title)
    {
        $this->pageTitle = $title;

        view()->share('pageTitle', $title);
    }
}
