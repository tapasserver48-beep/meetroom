<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;
}
