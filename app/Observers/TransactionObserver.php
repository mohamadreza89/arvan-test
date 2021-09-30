<?php

namespace App\Observers;

use App\Contracts\Notifiable;
use App\Notifications\BalanceWarningNotification;
use App\Services\AccountingService;
use App\Transaction;

class TransactionObserver
{
    /**
     * @var AccountingService
     */
    protected $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService= $accountingService;
    }

    public function created(Transaction $transaction)
    {
        $balance = $this->accountingService->userBalance($transaction->user_id);
        if ($balance <= 20000){
            $user = $transaction->user;
            $this->notify($user, $balance);
        }

        if ($balance < 0){
            $user->status = false;
        }
    }

    protected function notify(Notifiable $user, int $balance)
    {
        $user->notify(new BalanceWarningNotification($balance));
    }

}