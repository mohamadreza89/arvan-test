<?php

namespace App\Concerns;

use App\Invoice;
use App\User;

trait CreatesInvoice
{
    /**
     * @param User $user
     * @param $cost
     * @param $reason
     */
    protected function createInvoice(User $user, $cost, $reason)
    {
        if (! $cost)
            return;

        /** @var Invoice $invoice */
        $invoice = $user->invoices()->make(["amount" => $cost]);
        $invoice->reason()->associate($reason);
        $invoice->save();
    }
}