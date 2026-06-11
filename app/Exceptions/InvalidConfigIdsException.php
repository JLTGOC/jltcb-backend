<?php

namespace App\Exceptions;

use Exception;

class InvalidConfigIdsException extends Exception
{
    public function __construct(public readonly array $violations)
    {
        parent::__construct('The selected template configuration ids are invalid');
    }
}
