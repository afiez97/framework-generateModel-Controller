<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ArtisanServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register your command here
        $this->commands([
            \App\Console\Commands\GenerateModels::class,
        ]);
    }
}
