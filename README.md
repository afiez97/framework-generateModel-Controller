# framework-generateModel-Controller

 php artisan make:models

GenerateModels.php (/app/Console/Commands/GenerateModels.php) {path file setting setup}
     
     //RUN THIS COMMAND ON TERMINAL:
    touch app/Console/Commands/GenerateModels.php

ArtisanServiceProvider.php (/olive/imperolehan/api_imperolehan/app/Providers/ArtisanServiceProvider.php) {utk run command models}
   
    //RUN THIS COMMAND ON TERMINAL:
     php artisan make:provider ArtisanServiceProvider
     // or
     touch app/Providers/ArtisanServiceProvider.php

tmbah nie kat app.php

    $dotenv = Dotenv::createImmutable(__DIR__.'/../../../env_files','.imperolehan');
    $dotenv->load();

    $app->singleton(\App\Console\Commands\GenerateModels::class);

    $app->configure('database');
    $app->register(App\Providers\ArtisanServiceProvider::class);
