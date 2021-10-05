<?php

namespace App\UseCases;

use App\Concerns\CreatesInvoice;
use App\Http\Requests\Api\CreateArticle;
use App\Services\AccountingService;
use App\Services\CostService;
use App\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;

class ArticleCreation
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

    public function fire(User $user, array $data)
    {
        if (!$this->checkBalance($user, $this->costService->articleCost($user->id))) {
            throw new AuthorizationException();
        }

        return DB::transaction(function () use ($user, $data) {
            $article = $user->articles()->create([
                'title'       => $data['article']['title'],
                'description' => $data['article']['description'],
                'body'        => $data['article']['body'],
            ]);
            $this->accountingService->deductFromWallet($user->id, $this->costService->articleCost($user->id));
            $this->createInvoice($user, $this->costService->articleCost($user->id), $article);

            return $article;
        });
    }

    protected function checkBalance(Authenticatable $user, $cost): bool
    {
        return $this->accountingService->userBalance($user->id) >= $cost;
    }

}