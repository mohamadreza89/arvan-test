<?php

namespace App\Contracts;

interface TransactionRepositoryInterface
{
    public function userBalance($user_id);

    public function create($attributes);
}