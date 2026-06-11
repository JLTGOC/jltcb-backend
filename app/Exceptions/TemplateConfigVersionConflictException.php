<?php

namespace App\Exceptions;

use Exception;

class TemplateConfigVersionConflictException extends Exception
{
    public function __construct()
    {
        parent::__construct('Version Conflict! Your changes are based on an old Planning Template Configuration version. Reload and try again.');
    }
}
