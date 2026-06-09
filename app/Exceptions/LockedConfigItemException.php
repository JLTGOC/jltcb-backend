<?php

namespace App\Exceptions;

use Exception;

class LockedConfigItemException extends Exception
{
    public function __construct(public readonly array $violations)
    {
        parent::__construct('Invalid update: One or more config items are used by existing templates.');
    }
}
