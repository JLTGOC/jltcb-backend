<?php

namespace App\Support\Scramble;

use Dedoc\Scramble\Contracts\OperationTransformer;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\RouteInfo;

class SanctumAuthOperationTransformer implements OperationTransformer
{
    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        $middlewares = $routeInfo->route->gatherMiddleware();

        foreach ($middlewares as $middleware) {
            if (str_starts_with($middleware, 'auth:sanctum')) {
                // The browser sends the session cookie automatically when credentials are included.
                // Only the XSRF header needs to be supplied explicitly in docs.
                $operation->addSecurity(new SecurityRequirement(['xsrfToken' => []]));
                break;
            }
        }

        return $operation;
    }
}
