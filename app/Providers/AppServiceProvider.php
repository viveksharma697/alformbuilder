<?php

namespace App\Providers;

use App\Models\Form;
use App\Policies\FormPolicy;
use App\Services\AiFormService;
use App\Services\DocumentImportService;
use App\Services\FormSchemaValidator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FormSchemaValidator::class);
        $this->app->singleton(AiFormService::class);
        $this->app->singleton(DocumentImportService::class);
    }

    public function boot(): void
    {
        Gate::policy(Form::class, FormPolicy::class);
    }
}
