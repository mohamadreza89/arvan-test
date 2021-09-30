<?php

namespace App\Services;

use App\Transaction;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function userBalance($user_id): int
    {
        $records = DB::table("transactions")
          ->select(DB::raw("SUM(balance) as balance"))
          ->where("user_id", $user_id)
            ->groupBy("user_id");

        $item = $records->get()->first();
        if (! $item)
            return 0;

        return -$item->balance;

    }

    public function chargeWallet($user_id, $amount): void
    {
        Transaction::create([
            "user_id" => $user_id,
            "credit"=>$amount,
            "balance" => -$amount
        ]);

    }

    public function deductFromWallet($user_id, int $amount): void
    {
        Transaction::create([
            "user_id" => $user_id,
            "debt"=>$amount,
            "balance" => $amount
        ]);
    }

}