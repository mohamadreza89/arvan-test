<?php

namespace App\Services;

use App\Contracts\TransactionRepositoryInterface;

class AccountingService
{
    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function userBalance($user_id): int
    {
        return  $this->transactionRepository->userBalance($user_id);

    }

    public function chargeWallet($user_id, $amount): void
    {
        $this->transactionRepository->create([
            "user_id" => $user_id,
            "credit"=>$amount,
            "balance" => -$amount
        ]);

    }

    public function deductFromWallet($user_id, int $amount): void
    {
        $this->transactionRepository->create([
            "user_id" => $user_id,
            "debt"=>$amount,
            "balance" => $amount
        ]);
    }

}