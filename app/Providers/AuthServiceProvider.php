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

    // Define gates for Master menu based on roles
    Gate::define('view-master', function ($user) {
        return $user->hasRole('Admin'); // Change 'Admin' to your desired role
    });

    Gate::define('view-customers', function ($user) {
        return $user->hasRole('Admin'); // Define roles for Customer
    });

    Gate::define('view-products', function ($user) {
        return $user->hasRole('Admin'); // Define roles for Product
    });

    Gate::define('view-users', function ($user) {
        return $user->hasRole('Admin'); // Define roles for User
    });

    Gate::define('view-suppliers', function ($user) {
        return $user->hasRole('Admin'); // Define roles for Supplier
    });

    Gate::define('view-chart-accounts', function ($user) {
        return $user->hasRole('Admin'); // Define roles for Chart of Account
    });

    Gate::define('view-offices', function ($user) {
        return $user->hasRole('Admin'); // Define roles for Office
    });

    // Define gates for Transactional menu based on roles
    Gate::define('view-sales', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Finance 1') || $user->hasRole('Finance 2'); // Define roles for Sales
    });

    Gate::define('view-purchases', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Finance 1') || $user->hasRole('Finance 2'); // Define roles for Purchases
    });

    Gate::define('view-payments', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant')|| $user->hasRole('Finance 3'); // Define roles for Payments
    });

    // Define gates for Report menu based on roles
    Gate::define('view-general-ledger', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant'); // Define roles for General Ledger
    });

    Gate::define('view-balance-sheet', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant'); // Define roles for Balance Sheet
    });

    Gate::define('view-profit-loss', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant'); // Define roles for Profit Loss
    });

    Gate::define('view-journal-voucher', function ($user) {
        return $user->hasRole('Admin')|| $user->hasRole('Accountant');// Define roles for Journal Voucher
    });

    Gate::define('view-outstanding-sales', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant'); // Define roles for Outstanding Sales
    });

    Gate::define('view-outstanding-purchases', function ($user) {
        return $user->hasRole('Admin') || $user->hasRole('Accountant');; // Define roles for Outstanding Purchases
    });
}
}
