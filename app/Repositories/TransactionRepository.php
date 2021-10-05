<?php

namespace App\Repositories;

use App\Contracts\TransactionRepositoryInterface;
use App\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function userBalance($user_id)
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

    public function create($attributes)
    {
        return Transaction::create($attributes);
    }
}