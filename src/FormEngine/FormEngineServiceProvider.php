<?php

namespace Tomcroot\FormEngine;

use Illuminate\Support\ServiceProvider;

class FormEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/form-engine.php', 'form-engine');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/form-engine.php' => config_path('form-engine.php'),
        ], 'form-engine-config');
    }
}
