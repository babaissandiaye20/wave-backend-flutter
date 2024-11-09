<?php

namespace App\Providers;

use App\Services\TransactionService;
use App\Services\UtilisateurService;
use App\Repositories\CompteRepository;
use Illuminate\Support\ServiceProvider;
use App\Services\TypeTransactionService;
use App\Repositories\TransactionRepository;
use App\Repositories\UtilisateurRepository;
use App\Repositories\TypeTransactionRepository;
use App\Repositories\TransactionPlanifieeRepository;
use App\Services\Interfaces\TransactionServiceInterface;
use App\Services\Interfaces\UtilisateurServiceInterface;
use App\Repositories\Interfaces\CompteRepositoryInterface;
use App\Services\Interfaces\TypeTransactionServiceInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UtilisateurRepositoryInterface;
use App\Repositories\Interfaces\TypeTransactionRepositoryInterface;
use App\Repositories\Interfaces\TransactionPlanifieeRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
   
        $this->app->bind(CompteRepositoryInterface::class, CompteRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(TypeTransactionRepositoryInterface::class, TypeTransactionRepository::class);
        $this->app->bind(UtilisateurRepositoryInterface::class, UtilisateurRepository::class);
        $this->app->bind(UtilisateurServiceInterface::class, UtilisateurService::class);
        $this->app->bind(TypeTransactionServiceInterface::class, TypeTransactionService::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(TransactionPlanifieeRepositoryInterface::class, TransactionPlanifieeRepository::class);
        $this->app->bind(TransactionService::class, function ($app) {
            return new TransactionService(
                app(TransactionRepositoryInterface::class),
                app(TransactionPlanifieeRepositoryInterface::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
