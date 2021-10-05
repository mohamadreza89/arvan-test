<?php

namespace App\UseCases;

use App\Concerns\CreatesInvoice;
use App\Services\AccountingService;
use App\Services\CostService;

class BaseUseCase
{
    use CreatesInvoice;

    /**
     * @var AccountingService
     */
    protected $accountingService;

    /**
     * @var CostService
     */
    protected $costService;

    public function __construct(AccountingService $accountingService, CostService $costService)
    {
        $this->accountingService = $accountingService;
        $this->costService       = $costService;
    }
}