<?php

namespace App\Providers;

use App\Models\SalesInvoice; // Import SalesInvoice model
use App\Policies\SalesInvoicePolicy; // Import SalesInvoicePolicy
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     * 
     * 
     */
    public function register(): void
    {
        //
    }

    protected $policies = [
        SalesInvoice::class => SalesInvoicePolicy::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
