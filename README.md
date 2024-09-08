# framework-generateModel-Controller

 php artisan make:models

GenerateModels.php (/app/Console/Commands/GenerateModels.php) {path file setting setup}

ArtisanServiceProvider.php (/olive/imperolehan/api_imperolehan/app/Providers/ArtisanServiceProvider.php) {utk run command models}


tmbah nie kat app.php

    $dotenv = Dotenv::createImmutable(__DIR__.'/../../../env_files','.imperolehan');
    $dotenv->load();

    $app->singleton(\App\Console\Commands\GenerateModels::class);

    $app->configure('database');
    $app->register(App\Providers\ArtisanServiceProvider::class);
