<?php

namespace App\Jobs;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionPlanifiee;
use App\Services\TransactionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Compte;

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
            /** @var TransactionPlanifiee[] $transactionsPlanifiees */
            $transactionsPlanifiees = TransactionPlanifiee::with([
                'compte',
                'compte.utilisateur',
                'compteDestinataire',
                'compteDestinataire.utilisateur'
            ])
            ->where('statut', 'PLANIFIE')
            ->where('active', true)
            ->where('date_debut', '<=', Carbon::now())
            ->get();

            /** @var TransactionPlanifiee $transaction */
            foreach ($transactionsPlanifiees as $transaction) {
                try {
                    // Vérifier que tous les éléments nécessaires existent
                    if (!$transaction->compte || !$transaction->compte->utilisateur ||
                        !$transaction->compteDestinataire || !$transaction->compteDestinataire->utilisateur) {
                        Log::error("Données manquantes pour la transaction planifiée #{$transaction->id}");
                        $transaction->update(['statut' => 'ERREUR', 'active' => false]);
                        continue;
                    }

                    // Préparer les données pour le transfert planifié
                    $data = [
                        'type_transaction_id' => $transaction->type_transaction_id,
                        'compte_id' => $transaction->compte_id,
                        'montant' => $transaction->montant,
                        'frais' => $transaction->frais,
                        'receiver_phones' => [$transaction->compteDestinataire->utilisateur->telephone]
                    ];

                    // Utiliser la nouvelle méthode pour les transferts planifiés
                    $this->transactionService->effectuerTransfertPlanifie($data);

                    // Mettre à jour la planification
                    $prochaineDateExecution = $this->calculerProchaineDateExecution($transaction);
                    
                    if ($prochaineDateExecution === null) {
                        $transaction->update([
                            'active' => false,
                            'statut' => 'TERMINE'
                        ]);
                    } else {
                        $transaction->update([
                            'date_debut' => $prochaineDateExecution,
                            'statut' => 'PLANIFIE'
                        ]);
                    }
                } catch (Exception $e) {
                    Log::error("Erreur lors de l'exécution de la transaction planifiée #{$transaction->id}: " . $e->getMessage());
                    $transaction->update(['statut' => 'ERREUR']);
                }
            }
        } catch (Exception $e) {
            Log::error('Erreur lors de l\'exécution des transferts planifiés: ' . $e->getMessage());
            throw $e;
        }
    }


    private function calculerProchaineDateExecution(TransactionPlanifiee $transaction)
    {
        if (!($transaction instanceof TransactionPlanifiee)) {
            throw new Exception('Type invalide passé à calculerProchaineDateExecution');
        }

        $dateActuelle = Carbon::now();
        $dateDebut = Carbon::parse($transaction->date_debut);
        $nouvelleDate = null;

        switch ($transaction->frequence) {
            case 'everyminute':
                $nouvelleDate = $dateDebut->copy()->addMinute();
                if ($nouvelleDate->diffInMinutes($dateActuelle) > 5) {
                    return null;
                }
                break;
            case 'everyday':
                $nouvelleDate = $dateDebut->copy()->addDay();
                if ($nouvelleDate->diffInDays($dateActuelle) > 7) {
                    return null;
                }
                break;
            case 'weekly':
                $nouvelleDate = $dateDebut->copy()->addWeek();
                if ($nouvelleDate->diffInWeeks($dateActuelle) > 4) {
                    return null;
                }
                break;
            case 'monthly':
                $nouvelleDate = $dateDebut->copy()->addMonth();
                if ($nouvelleDate->diffInMonths($dateActuelle) > 12) {
                    return null;
                }
                break;
            default:
                return null;
        }

        return $nouvelleDate;
    }
}
