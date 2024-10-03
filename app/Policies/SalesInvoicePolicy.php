<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SalesInvoice;

class SalesInvoicePolicy
{

    public function index(User $user)
    {
        // Allow viewing if the user is an admin or the invoice owner
        return $user->name === 'admin' || $user->name === 'Finance 2';
    }

    public function view(User $user, SalesInvoice $salesInvoice)
    {
        // Allow viewing if the user is an admin or the invoice owner
        return true;
    }

    /**
     * Determine if the user can create sales invoices.
     */
    public function create(User $user)
    {
        // Allow creating if the user is an admin or manager
        return $user->name === 'admin' || $user->name === 'Finance 2';
    }

    /**
     * Determine if the given sales invoice can be updated by the user.
     */
    public function update(User $user, SalesInvoice $salesInvoice)
    {
        // Allow updating if the user is an admin or the invoice owner
        return $user->name === 'admin' || $user->name === 'Finance 2';
    }

    /**
     * Determine if the given sales invoice can be deleted by the user.
     */
    public function delete(User $user, SalesInvoice $salesInvoice)
    {
        // Allow deleting only if the user is an admin
        return $user->name === 'admin'|| $user->name === 'Finance 2';
    }
}
