<?php

namespace Aventus\Laraventus;

use Aventus\Laraventus\Middlewares\RouteMatchedSubscriber;
use Aventus\Laraventus\Routes\ResourceRegistrar;
use Aventus\Laraventus\Tools\Console;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as SP;
use Illuminate\Support\Facades\Route;

class ServiceProvider extends SP
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
        $this->publishes([
            __DIR__ . '/config/laraventus.php' => config_path('laraventus.php'),
        ]);

        Route::macro('resourceWithMany', function (string $name, string $controller, array $options = []) {
            /** @var ResourceRegistrar */
            $registrar = app('Aventus\Laraventus\Routes\ResourceRegistrar');
            return $registrar->register($name, $controller, $options);
        });

        $watchCtrl = config('laraventus.controller.attributes') ?? true;
        if ($watchCtrl) {
            Event::subscribe(RouteMatchedSubscriber::class);
        }
    }
}
