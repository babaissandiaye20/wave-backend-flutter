<?php

use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\TypeTransactionController;
Route::post('/utilisateurs', [UtilisateurController::class, 'store']);




Route::post('login', [AuthController::class, 'login']);

Route::get('/types-transactions', [TypeTransactionController::class, 'index']);
Route::get('/types-transactions/{id}', [TypeTransactionController::class, 'show']);
Route::post('/types-transactions', [TypeTransactionController::class, 'store']);
Route::put('/types-transactions/{id}', [TypeTransactionController::class, 'update']);
Route::delete('/types-transactions/{id}', [TypeTransactionController::class, 'destroy']);

Route::middleware('auth:api')->group(function () {
  

    
});
