<?php

namespace App\Providers;

use App\Models\BillingConfiguration;
use App\Support\Scramble\SanctumAuthOperationTransformer;
use Dedoc\Scramble\Configuration\OperationTransformers;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Conversation;
use App\Models\DetailsConfiguration;
use App\Models\MessageTemplate;
use App\Models\StandardConfiguration;
use App\Policies\ChatPolicy;
use App\Policies\ConfigurationPolicy;
use Spatie\Permission\Models\Role;
use App\Policies\RolePolicy;

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

        Scramble::configure()
        ->withOperationTransformers(function (Operation $operation) {
            $operation->addParameters([
                Parameter::make('Platform', 'header')
                    ->description("Use this header to toggle between mobile and web-specific response data. Defaults to mobile if omitted.")
                    ->setSchema(Schema::fromType(new StringType()))
                    ->required(false)
                    ->example('web / mobile'),
            ]);
        });

        Gate::policy(Conversation::class, ChatPolicy::class);
        Gate::policy(BillingConfiguration::class, ConfigurationPolicy::class);
        Gate::policy(DetailsConfiguration::class, ConfigurationPolicy::class);
        Gate::policy(MessageTemplate::class, ConfigurationPolicy::class);
        Gate::policy(StandardConfiguration::class, ConfigurationPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
    }
}
