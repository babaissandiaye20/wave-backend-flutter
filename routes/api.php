<?php

use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\TypeTransactionController;
use App\Http\Controllers\TransactionController;
Route::post('/utilisateurs', [UtilisateurController::class, 'store']);




Route::post('login', [AuthController::class, 'login']);

Route::get('/types-transactions', [TypeTransactionController::class, 'index']);
Route::get('/types-transactions/{id}', [TypeTransactionController::class, 'show']);
Route::post('/types-transactions', [TypeTransactionController::class, 'store']);
Route::put('/types-transactions/{id}', [TypeTransactionController::class, 'update']);
Route::delete('/types-transactions/{id}', [TypeTransactionController::class, 'destroy']);

/* Route::post('/effectuer', [TransactionController::class, 'effectuerTransaction']); */
Route::post('/transactions', [TransactionController::class, 'effectuerTransaction']);

Route::prefix('transactions')->group(function () {
 
    Route::get('/', [TransactionController::class, 'obtenirToutesTransactions']);
    Route::get('/{id}', [TransactionController::class, 'obtenirTransactionParId']);
    Route::put('/{id}', [TransactionController::class, 'mettreAJourTransaction']);
    Route::delete('/{id}', [TransactionController::class, 'supprimerTransaction']);
});

Route::put('/utilisateurs/{id}/planifier', [UtilisateurController::class, 'markAsPlanned']);
Route::post('/transactions/planifier', [TransactionController::class, 'planifierTransaction']);

Route::middleware('auth:api')->group(function () {
  

    
});
