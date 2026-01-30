<?php

namespace App\Http\Controllers;
use App\Traits\{
    ResponseAPI,
    Generator
};

abstract class Controller
{
    use ResponseAPI, Generator;
}
