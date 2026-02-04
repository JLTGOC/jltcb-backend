<?php

namespace App\Http\Controllers;
use App\Traits\{
    ResponseAPI
};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use ResponseAPI;
    use AuthorizesRequests;
}
