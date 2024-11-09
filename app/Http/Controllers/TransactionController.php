<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TransactionService;
use Exception;

class TransactionController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Effectuer une transaction.
     */
    public function effectuerTransaction(Request $request)
    {
        $data = $request->all();

        try {
            $transaction = $this->transactionService->effectuerTransaction($data);
            return response()->json(['success' => true, 'transaction' => $transaction], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtenir toutes les transactions.
     */
    public function obtenirToutesTransactions()
    {
        try {
            $transactions = $this->transactionService->obtenirToutesTransactions();
            return response()->json(['success' => true, 'transactions' => $transactions], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtenir une transaction par ID.
     */
    public function obtenirTransactionParId($id)
    {
        try {
            $transaction = $this->transactionService->obtenirTransactionParId($id);
            return response()->json(['success' => true, 'transaction' => $transaction], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 404);
        }
    }

    /**
     * Mettre à jour une transaction.
     */
    public function mettreAJourTransaction(Request $request, $id)
    {
        $data = $request->all();

        try {
            $transaction = $this->transactionService->mettreAJourTransaction($id, $data);
            return response()->json(['success' => true, 'transaction' => $transaction], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer une transaction.
     */
    public function supprimerTransaction($id)
    {
        try {
            $this->transactionService->supprimerTransaction($id);
            return response()->json(['success' => true, 'message' => 'Transaction supprimée avec succès.'], 200);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    public function planifierTransaction(Request $request)
    {
        $data = $request->all();

        try {
            $transactionPlanifiee = $this->transactionService->planifierTransfert($data);
            return response()->json(['success' => true, 'transactionPlanifiee' => $transactionPlanifiee], 201);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
