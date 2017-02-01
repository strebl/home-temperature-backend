<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use InfluxDB\Client;
use InfluxDB\Database;

class InfluxDBServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Client::class, function () {
            return new Client(config('influxdb.host'));
        });

        $this->app->singleton(Database::class, function ($app) {
            return $app->make(Client::class)->selectDB(config('influxdb.database'));
        });
    }
}
