<?php

namespace App\Repositories;

use App\Traits\Pagination;
use App\Traits\ResponseAPI;

class BaseRepository
{
    use Pagination, ResponseAPI;
}
