<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
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
    public function boot()
{
    $this->registerPolicies();

    Gate::define('view-master', function ($user) {
        return $user->hasRole('Admin')|| $user->hasRole('Accountant'); 
    });

    Gate::define('view-customers', function ($user) {
        return $user->hasRole('Admin'); 
    });

    Gate::define('view-products', function ($user) {
        return $user->hasRole('Admin'); 
    });

    Gate::define('view-users', function ($user) {
        return $user->hasRole('Admin'); 
    });

    Gate::define('view-suppliers', function ($user) {
        return $user->hasRole('Admin'); 
    });

    Gate::define('view-chart-accounts', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant'); 
    });

    Gate::define('view-offices', function ($user) {
        return $user->hasRole('Admin'); 
    });

    Gate::define('view-transactional', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Finance 1') || $user->hasRole('Finance 2'); 
    });

    Gate::define('view-sales', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Finance 1') || $user->hasRole('Finance 2'); 
    });

    Gate::define('view-purchases', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Finance 1') || $user->hasRole('Finance 2'); 
    });

    Gate::define('view-payments', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Finance 3'); 
    });

    Gate::define('view-general-ledger', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant'); 
    });

    Gate::define('view-balance-sheet', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant'); 
    });

    Gate::define('view-profit-loss', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant'); 
    });

    Gate::define('view-journal-voucher', function ($user) {
        return $user->hasRole('Admin')|| $user->hasRole('Accountant');
    });

    Gate::define('view-outstanding-sales', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant');
    });

    Gate::define('view-outstanding-purchases', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant');; 
    });
}
}
