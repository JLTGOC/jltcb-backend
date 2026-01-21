<?php

namespace App\Providers;

use App\Support\Scramble\SanctumAuthOperationTransformer;
use Dedoc\Scramble\Configuration\OperationTransformers;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Add bearer auth scheme to the generated OpenAPI document
        Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->components->addSecurityScheme(
                'sanctum',
                SecurityScheme::http('bearer', 'Sanctum')
                    ->as('sanctum')
                    ->setDescription('Use Sanctum bearer token in the Authorization header (Authorization: Bearer <token>)'),
            );
        });

        // Mark operations protected by auth:sanctum as secured in docs
        Scramble::configure()->withOperationTransformers(function (OperationTransformers $transformers) {
            $transformers->append(SanctumAuthOperationTransformer::class);
        });
    }
}
