<?php

namespace SuperPlatform\UnitedTicket;

use Illuminate\Support\ServiceProvider;
use SuperPlatform\UnitedTicket\Console\Commands\AllBetEGameFetchTicketCommand;
use SuperPlatform\UnitedTicket\Console\Commands\AutoFetchTicketCommand;
use SuperPlatform\UnitedTicket\Console\Commands\ManualStoredBlockCommand;
use SuperPlatform\UnitedTicket\Console\Commands\UnitedIntegratorTransferCommand;
use SuperPlatform\UnitedTicket\Console\Commands\UnitedTicketStoredBlockCommand;
use SuperPlatform\UnitedTicket\Console\Commands\UnitedTicketStationsCommand;

class UnitedTicketServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // 合併套件設定檔
        $this->mergeConfigFrom(
            __DIR__ . '/../config/united_ticket.php', 'united_ticket'
        );

        // include helpers after 合併套件設定檔
        $this->includeHelpers();

        $this->publishes([
            __DIR__ . '/../database/migrations'
            => database_path('migrations'),
        ]);

//        if ($this->app->runningInConsole()) {
            // 執行所有套件 migrations
//            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
            // 註冊所有 commands
            $this->commands([
                UnitedIntegratorTransferCommand::class,
                UnitedTicketStoredBlockCommand::class,
                UnitedTicketStationsCommand::class,
                ManualStoredBlockCommand::class,
                AutoFetchTicketCommand::class,
                AllBetEGameFetchTicketCommand::class,
            ]);
//        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(
            'SuperPlatform\ApiCaller\ApiCallerServiceProvider'
        );

        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('ticket_integrator', 'SuperPlatform\UnitedTicket\TicketIntegrator');
        $loader->alias('block_united_ticket', 'SuperPlatform\UnitedTicket\BlockUnitedTicket');
    }

    /**
     * include helpers
     */
    protected function includeHelpers()
    {
        $file = __DIR__ . '/Helpers/ExceptionHelper.php';
        if (file_exists($file)) {
            require_once($file);
        }
    }
}