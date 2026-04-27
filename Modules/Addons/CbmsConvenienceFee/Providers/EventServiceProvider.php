<?php

namespace Modules\Addons\CbmsConvenienceFee\Providers;

// use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\Events\InvoiceCreated::class => [
            \Modules\Addons\CbmsConvenienceFee\Listeners\AddCFToInvoiceItems::class,
        ],
        \App\Events\InvoiceChangeGateway::class => [
            \Modules\Addons\CbmsConvenienceFee\Listeners\UpdateCFToInvoiceItems::class,
        ],
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
