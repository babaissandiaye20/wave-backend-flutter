<?php

namespace App\Jobs;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionPlanifiee;
use App\Services\TransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ExecuteScheduledTransfers implements ShouldQueue
{
    use Dispatchable;

    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function handle()
    {
        try {
            // Récupérer les transactions planifiées qui doivent être exécutées
            $transactionsPlanifiees = TransactionPlanifiee::where('statut', 'PLANIFIE')
                ->where('date_debut', '<=', Carbon::now())
                ->get();

            foreach ($transactionsPlanifiees as $transaction) {
                $data = [
                    'type_transaction_id' => $transaction->type_transaction_id,
                    'sender_id' => $transaction->compte_id,
                    'receivers' => [
                        [
                            'receiver_id' => $transaction->compte_destinataire_id
                        ]
                    ],
                    'montant' => $transaction->montant,
                    'frais' => $transaction->frais
                ];

                // Appeler le service pour exécuter le transfert
                $this->transactionService->effectuerTransaction($data);

                // Mettre à jour le statut de la transaction
                $transaction->statut = 'EXECUTE';
                $transaction->save();
            }
        } catch (Exception $e) {
            Log::error('Erreur lors de l\'exécution des transferts planifiés: ' . $e->getMessage());
        }
    }
}
