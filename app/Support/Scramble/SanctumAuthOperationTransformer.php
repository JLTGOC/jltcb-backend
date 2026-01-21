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
                $operation->addSecurity(new SecurityRequirement(['sanctum' => []]));
                break;
            }
        }

        return $operation;
    }
}
