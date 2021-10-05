<?php

namespace App\Providers;

use App\Contracts\TransactionRepositoryInterface;
use App\Observers\TransactionObserver;
use App\Repositories\TransactionRepository;
use App\Services\CostService;
use App\Transaction;
use Schema;
use Illuminate\Support\ServiceProvider;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        Transaction::observe(TransactionObserver::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment() !== 'production') {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        $this->app->singleton(CostService::class, function (){
            return new CostService(config("costs.comment"), config("costs.article"));
        });

        $this->app->singleton(TransactionRepositoryInterface::class, TransactionRepository::class);
    }
}
