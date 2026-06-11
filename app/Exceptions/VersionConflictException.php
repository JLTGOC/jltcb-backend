<?php

namespace App\Exceptions;

use Exception;

class VersionConflictException extends Exception
{
    public function __construct(public readonly array $conflicts)
    {
        parent::__construct('Version conflict detected.');
    }
}
