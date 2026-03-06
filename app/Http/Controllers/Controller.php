<?php

namespace App\Http\Controllers;
use App\Traits\{
    Pagination,
    ResponseAPI
};
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use Pagination;
    use ResponseAPI;
    use AuthorizesRequests;
}
