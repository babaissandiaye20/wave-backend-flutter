<?php

namespace App\Services;

use Exception;
use App\Models\Compte;
use App\Models\Utilisateur;
use Illuminate\Support\Carbon;
use App\Models\TypeTransaction;
use Illuminate\Support\Facades\DB;

use App\Models\TransactionPlanifiee;
use App\Services\Interfaces\TransactionServiceInterface;
use App\Repositories\Interfaces\TransactionPlanifieeRepository;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\TransactionPlanifieeRepositoryInterface;
use Illuminate\Support\Facades\Log;

class TransactionService implements TransactionServiceInterface
{
    protected $transactionPlanifieeRepository;
    protected $transactionRepository;
    private $typeTransactions;
private const POURCENTAGE_FRAIS = 0.01;

    public function __construct(TransactionRepositoryInterface $transactionRepository, TransactionPlanifieeRepositoryInterface $transactionPlanifieeRepository)
    {
        $this->transactionPlanifieeRepository = $transactionPlanifieeRepository;
       
        $this->transactionRepository = $transactionRepository;
        $this->typeTransactions = TypeTransaction::pluck('nom', 'id')->toArray();
    
    }

    /**
     * Effectuer une transaction sur un compte en fonction du type de transaction
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function effectuerTransaction(array $data)
    {
        DB::beginTransaction();

        try {
            if (!isset($data['type_transaction_id']) || !isset($this->typeTransactions[$data['type_transaction_id']])) {
                throw new Exception("Type de transaction invalide.");
            }

            $typeTransactionNom = $this->typeTransactions[$data['type_transaction_id']];

            // Si c'est un transfert, on utilise sender_id pour obtenir le compte
            if (strtoupper($typeTransactionNom) === 'TRANSFERT') {
                $sender = Utilisateur::findOrFail($data['sender_id']);
                $data['compte_id'] = $sender->comptes()->firstOrFail()->id;
            }

            // Vérifier que compte_id existe pour les autres types de transactions
            if (!isset($data['compte_id']) && strtoupper($typeTransactionNom) !== 'TRANSFERT') {
                throw new Exception("L'ID du compte est requis pour ce type de transaction.");
            }

            switch (strtoupper($typeTransactionNom)) {
                case 'DEPOT':
                    $transaction = $this->depot($data);
                    break;
                case 'RETRAIT':
                    $transaction = $this->retrait($data);
                    break;
                case 'TRANSFERT':
                    $transaction = $this->transfert($data);
                    break;
                case 'PAIEMENT':
                    $transaction = $this->payment($data);
                    break;
                default:
                    throw new Exception("Type de transaction non pris en charge: " . $typeTransactionNom);
            }

            DB::commit();
            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Erreur lors de la transaction: " . $e->getMessage());
        }
    }

    /**
     * Dépôt d'argent dans un compte
     */
    private function depot(array $data)
    {
        $compte = Compte::find($data['compte_id']);
        if (!$compte) {
            throw new Exception("Compte introuvable.");
        }

        $compte->solde += $data['montant'];
        $compte->save();

        return $this->transactionRepository->create($data);
    }

    /**
     * Retrait d'argent depuis un compte
     */
    private function retrait(array $data)
    {
        $compte = Compte::find($data['compte_id']);
        if (!$compte) {
            throw new Exception("Compte introuvable.");
        }

        if ($compte->solde < $data['montant']) {
            throw new Exception("Solde insuffisant pour effectuer ce retrait.");
        }

        $compte->solde -= $data['montant'];
        $compte->save();

        return $this->transactionRepository->create($data);
    }

    /**
     * Transfert d'argent d'un compte à un autre
     */
    private function transfert(array $data)
    {
        // Récupérer le compte émetteur via sender_id
        $sender = Utilisateur::findOrFail($data['sender_id']);
        $senderCompte = $sender->comptes()->firstOrFail();

        // Valider la liste des destinataires
        if (empty($data['receivers'])) {
            throw new Exception("La liste des destinataires ne peut pas être vide.");
        }

        $montantBase = $data['montant'];
        $frais = $montantBase * self::POURCENTAGE_FRAIS;
        $appliquerFrais = isset($data['frais']) && $data['frais'] === true;

        // Déterminer qui paie les frais
        if ($appliquerFrais) {
            // L'émetteur paie les frais
            $montantADebiter = $montantBase + $frais;
            $montantACrediter = $montantBase;
        } else {
            // Le destinataire paie les frais
            $montantADebiter = $montantBase;
            $montantACrediter = $montantBase - $frais;
        }

        // Calculer le montant total à débiter pour tous les destinataires
        $nombreDestinataires = count($data['receivers']);
        $montantTotalADebiter = $montantADebiter * $nombreDestinataires;

        // Vérifier si le solde est suffisant
        if ($senderCompte->solde < $montantTotalADebiter) {
            throw new Exception("Solde insuffisant pour effectuer ces transferts. Montant total nécessaire: " . $montantTotalADebiter);
        }

        $transactions = [];

        DB::transaction(function () use ($data, $senderCompte, $montantADebiter, $montantACrediter, $montantBase, $frais, $appliquerFrais, &$transactions) {
            // Débiter le compte émetteur
            $senderCompte->solde -= $montantADebiter;
            $senderCompte->save();

            // Créer une transaction pour chaque destinataire
            foreach ($data['receivers'] as $receiver) {
                $receiverUser = Utilisateur::findOrFail($receiver['receiver_id']);
                $receiverCompte = $receiverUser->comptes()->firstOrFail();

                // Créditer le compte destinataire
                $receiverCompte->solde += $montantACrediter;
                $receiverCompte->save();

                // Créer la transaction
                $transactionData = [
                    'compte_id' => $senderCompte->id,
                    'type_transaction_id' => $data['type_transaction_id'],
                    'montant' => $montantBase,
                    'montant_debite' => $montantADebiter,
                    'montant_credite' => $montantACrediter,
                    'frais' => $appliquerFrais,
                    'montant_frais' => $frais,
                    'frais_paye_par' => $appliquerFrais ? 'emetteur' : 'destinataire',
                    'receiver_id' => $receiver['receiver_id']
                ];

                $transactions[] = $this->transactionRepository->create($transactionData);
            }
        });

        return $transactions;
    }

    /**
     * Paiement à partir d'un compte
     */
    private function payment(array $data)
    {
        $compte = Compte::find($data['compte_id']);
        if (!$compte) {
            throw new Exception("Compte introuvable.");
        }

        if ($compte->solde < $data['montant']) {
            throw new Exception("Solde insuffisant pour effectuer ce paiement.");
        }

        $compte->solde -= $data['montant'];
        $compte->save();

        return $this->transactionRepository->create($data);
    }

    public function planifierTransfert(array $data)
    {
        // Validation des données
        $this->validatePlanificationData($data);

        // Récupérer le compte émetteur
        $sender = Utilisateur::findOrFail($data['sender_id']);
        $senderCompte = $sender->comptes()->firstOrFail();

        // Calculer les montants et frais
        $montantBase = $data['montant'];
        $frais = $montantBase * self::POURCENTAGE_FRAIS;
        $appliquerFrais = isset($data['frais']) && $data['frais'] === true;
        
        $montantADebiter = $appliquerFrais ? $montantBase + $frais : $montantBase;
        $montantACrediter = $appliquerFrais ? $montantBase : $montantBase - $frais;

        $transactionsPlanifiees = [];
        
        try {
            // Créer une référence au repository
            $repository = $this->transactionPlanifieeRepository;
            
            DB::transaction(function () use (
                $data, 
                $sender,
                $senderCompte, 
                $montantADebiter, 
                $montantACrediter, 
                $montantBase, 
                $frais, 
                $appliquerFrais, 
                &$transactionsPlanifiees,
                $repository  // Utiliser la référence au lieu de $this
            ) {
                foreach ($data['receivers'] as $receiver) {
                    // Valider le destinataire
                    $receiverUser = Utilisateur::findOrFail($receiver['receiver_id']);
                    $receiverCompte = $receiverUser->comptes()->firstOrFail();

                    $transactionData = [
                        'compte_id' => $senderCompte->id,
                        'compte_destinataire_id' => $receiverCompte->id,
                        'type_transaction_id' => $data['type_transaction_id'],
                        'montant' => $montantBase,
                        'montant_debite' => $montantADebiter,
                        'montant_credite' => $montantACrediter,
                        'frais' => $appliquerFrais,
                        'montant_frais' => $frais,
                        'frais_paye_par' => $appliquerFrais ? 'emetteur' : 'destinataire',
                        'receiver_id' => $receiver['receiver_id'],
                        'date_debut' => Carbon::parse($data['date_planification']),
                        'frequence' => $data['frequence'] ?? 'monthly',
                        'active' => true,
                        'statut' => 'PLANIFIE'
                    ];

                    $transactionPlanifiee = $repository->create($transactionData);
                    $transactionsPlanifiees[] = $transactionPlanifiee;
                }

                // Marquer l'utilisateur comme ayant des transactions planifiées
                $sender->markAsPlanned();
            });

            return $transactionsPlanifiees;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la planification du transfert: " . $e->getMessage());
        }
    }
    /**
     * Valider les données de planification
     * @param array $data
     * @throws Exception
     */
    private function validatePlanificationData(array $data)
    {
        if (!isset($data['type_transaction_id']) || !isset($this->typeTransactions[$data['type_transaction_id']]) || 
            strtoupper($this->typeTransactions[$data['type_transaction_id']]) !== 'TRANSFERT') {
            throw new Exception("Le type de transaction doit être 'TRANSFERT'.");
        }

        if (empty($data['receivers'])) {
            throw new Exception("La liste des destinataires ne peut pas être vide.");
        }

        if (!isset($data['date_planification'])) {
            throw new Exception("La date de planification est requise.");
        }

        if (!isset($data['montant']) || $data['montant'] <= 0) {
            throw new Exception("Le montant doit être supérieur à 0.");
        }

        $dateDebut = Carbon::parse($data['date_planification']);
        if ($dateDebut->isPast()) {
            throw new Exception("La date de planification ne peut pas être dans le passé.");
        }

        // Mettre à jour la validation de la fréquence pour correspondre au schéma de la base de données
        if (isset($data['frequence']) && !in_array($data['frequence'], ['monthly', 'everyminute', 'everyday', 'weekly'])) {
            throw new Exception("Fréquence invalide. Les valeurs acceptées sont: monthly, everyminute, everyday, weekly");
        }
    }

    /**
     * Mettre à jour la date de la prochaine exécution
     */
    private function updateNextExecutionDate(TransactionPlanifiee $transactionPlanifiee)
    {
        $dateDebut = Carbon::parse($transactionPlanifiee->date_debut);

        switch ($transactionPlanifiee->frequence) {
            case 'everyminute':
                $dateDebut->addMinute();
                break;
            case 'everyday':
                $dateDebut->addDay();
                break;
            case 'weekly':
                $dateDebut->addWeek();
                break;
            case 'monthly':
                $dateDebut->addMonth();
                break;
        }

        $transactionPlanifiee->date_debut = $dateDebut;
        $transactionPlanifiee->save();
    }
    /**
     * Exécuter les transferts planifiés
     */
    public function executerTransfertsPlanifies()
    {
        $transactionsPlanifiees = TransactionPlanifiee::where('active', true)
            ->where('statut', 'PLANIFIE')
            ->where('date_debut', '<=', now())
            ->get();

        foreach ($transactionsPlanifiees as $transactionPlanifiee) {
            DB::beginTransaction();

            try {
                // Préparer les données pour le transfert
                $data = [
                    'sender_id' => $transactionPlanifiee->compte->utilisateur->id,
                    'type_transaction_id' => $transactionPlanifiee->type_transaction_id,
                    'montant' => $transactionPlanifiee->montant,
                    'frais' => $transactionPlanifiee->frais,
                    'receivers' => [
                        [
                            'receiver_id' => $transactionPlanifiee->receiver_id
                        ]
                    ]
                ];

                // Effectuer le transfert
                $this->effectuerTransaction($data);

                // Mettre à jour la date de la prochaine exécution
                $this->updateNextExecutionDate($transactionPlanifiee);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                // Logger l'erreur ou notifier l'administrateur
                Log::error("Erreur lors de l'exécution du transfert planifié #{$transactionPlanifiee->id}: " . $e->getMessage());
                continue;
            }
        }
    }

    /**
     * Mettre à jour la date de la prochaine exécution
     */
   
    // Méthodes pour obtenir, mettre à jour et supprimer des transactions
    public function obtenirToutesTransactions() { /* ... */ }
    public function obtenirTransactionParId(int $id) { /* ... */ }
    public function mettreAJourTransaction(int $id, array $data) { /* ... */ }
    public function supprimerTransaction(int $id) { /* ... */ }
}
