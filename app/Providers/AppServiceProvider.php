<?php

namespace App\Providers;

use App\Services\UtilisateurService;
use App\Repositories\CompteRepository;
use Illuminate\Support\ServiceProvider;
use App\Services\TypeTransactionService;
use App\Repositories\TransactionRepository;
use App\Repositories\UtilisateurRepository;
use App\Repositories\TypeTransactionRepository;
use App\Services\Interfaces\UtilisateurServiceInterface;
use App\Repositories\Interfaces\CompteRepositoryInterface;
use App\Services\Interfaces\TypeTransactionServiceInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\UtilisateurRepositoryInterface;
use App\Repositories\Interfaces\TypeTransactionRepositoryInterface;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
