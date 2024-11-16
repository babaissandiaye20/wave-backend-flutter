<?php

namespace App\Services;
use Exception;
use App\Models\Compte;
use App\Models\Transaction;
use App\Models\Utilisateur;
use Illuminate\Support\Carbon;
use App\Models\TypeTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionPlanifiee;
use Illuminate\Support\Facades\Auth;
use App\Services\Interfaces\TransactionServiceInterface;
use App\Repositories\Interfaces\TransactionPlanifieeRepository;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\TransactionPlanifieeRepositoryInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;


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

        // Pour un transfert ou un payement, on utilise l'utilisateur authentifié comme expéditeur
        if (in_array(strtoupper($typeTransactionNom), ['TRANSFERT', 'PAYEMENT'])) {
            // Récupérer l'ID de l'utilisateur connecté
            if (!auth('api')->check()) {
                throw new Exception("Utilisateur non authentifié.");
            }
            
            $userId = Auth::id();
            $sender = Utilisateur::findOrFail($userId);
            $senderCompte = $sender->comptes()->first();
            
            if (!$senderCompte) {
                throw new Exception("Aucun compte trouvé pour cet utilisateur.");
            }

            // Ajouter l'ID du compte aux données
            $data['compte_id'] = $senderCompte->id;
            $data['sender_id'] = $sender->id;
        }

        // Vérifier que compte_id existe pour les autres types de transactions
        if (!isset($data['compte_id'])) {
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
            case 'PAYEMENT':  // Correction pour correspondre à la valeur en base de données
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

    private function transfert(array $data)
    {
        // Récupérer le compte émetteur via l'utilisateur authentifié
        $userId = Auth::id();
        if (!$userId) {
            throw new Exception("Utilisateur non authentifié.");
        }
        $sender = Utilisateur::findOrFail($userId);
        $senderCompte = $sender->comptes()->firstOrFail();
    
        // Valider la liste des numéros de téléphone des destinataires
        if (empty($data['receiver_phones'])) {
            throw new Exception("La liste des numéros de téléphone des destinataires ne peut pas être vide.");
        }
    
        $montantBase = $data['montant'];
        $frais = $montantBase * self::POURCENTAGE_FRAIS;
        $appliquerFrais = isset($data['frais']) && $data['frais'] === true;
    
        // Déterminer qui paie les frais
        if ($appliquerFrais) {
            $montantADebiter = $montantBase + $frais;
            $montantACrediter = $montantBase;
        } else {
            $montantADebiter = $montantBase;
            $montantACrediter = $montantBase - $frais;
        }
    
        // Calculer le montant total à débiter pour tous les destinataires
        $nombreDestinataires = count($data['receiver_phones']);
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
            foreach ($data['receiver_phones'] as $phoneNumber) {
                // Rechercher l'utilisateur par numéro de téléphone
                $receiverUser = Utilisateur::where('telephone', $phoneNumber)->first();
                if (!$receiverUser) {
                    throw new Exception("Aucun utilisateur trouvé avec le numéro de téléphone: " . $phoneNumber);
                }
    
                $receiverCompte = $receiverUser->comptes()->firstOrFail();
    
                // Créditer le compte destinataire
                $receiverCompte->solde += $montantACrediter;
                $receiverCompte->save();
    
                // Créer la transaction
                $transactionData = [
                    'compte_id' => $senderCompte->id,
                    'compte_destinataire_id' => $receiverCompte->id,
                    'type_transaction_id' => $data['type_transaction_id'],
                    'montant' => $montantBase,
                    'montant_debite' => $montantADebiter,
                    'montant_credite' => $montantACrediter,
                    'frais' => $appliquerFrais,
                    'montant_frais' => $frais,
                    'frais_paye_par' => $appliquerFrais ? 'emetteur' : 'destinataire'
                ];
    
                $transactions[] = $this->transactionRepository->create($transactionData);
            }
        });
    
        return $transactions;
    }

    private function payment(array $data)
    {
        // Récupérer le compte émetteur via l'utilisateur authentifié
        $userId = Auth::id();
        if (!$userId) {
            throw new Exception("Utilisateur non authentifié.");
        }
        $sender = Utilisateur::findOrFail($userId);
        $senderCompte = $sender->comptes()->firstOrFail();
    
        // Valider la liste des numéros de téléphone des destinataires
        if (empty($data['receiver_phones'])) {
            throw new Exception("La liste des numéros de téléphone des destinataires ne peut pas être vide.");
        }
    
        $montantBase = $data['montant'];
        // Pas de frais pour les paiements
        $montantADebiter = $montantBase;
        $montantACrediter = $montantBase;
    
        // Calculer le montant total à débiter pour tous les destinataires
        $nombreDestinataires = count($data['receiver_phones']);
        $montantTotalADebiter = $montantADebiter * $nombreDestinataires;
    
        // Vérifier si le solde est suffisant
        if ($senderCompte->solde < $montantTotalADebiter) {
            throw new Exception("Solde insuffisant pour effectuer ces paiements. Montant total nécessaire: " . $montantTotalADebiter);
        }
    
        $transactions = [];
    
        DB::transaction(function () use ($data, $senderCompte, $montantADebiter, $montantACrediter, $montantBase, &$transactions) {
            // Débiter le compte émetteur
            $senderCompte->solde -= $montantADebiter;
            $senderCompte->save();
    
            // Créer une transaction pour chaque destinataire
            foreach ($data['receiver_phones'] as $phoneNumber) {
                // Rechercher l'utilisateur marchand par numéro de téléphone
                $receiverUser = Utilisateur::where('telephone', $phoneNumber)
                                         ->where('role', 'marchand')
                                         ->first();
                if (!$receiverUser) {
                    throw new Exception("Aucun marchand trouvé avec le numéro de téléphone: " . $phoneNumber);
                }
    
                $receiverCompte = $receiverUser->comptes()->firstOrFail();
    
                // Vérifier que le destinataire n'est pas le même que l'expéditeur
                if ($receiverCompte->id === $senderCompte->id) {
                    throw new Exception("Vous ne pouvez pas effectuer un paiement vers votre propre compte.");
                }
    
                // Créditer le compte destinataire
                $receiverCompte->solde += $montantACrediter;
                $receiverCompte->save();
    
                // Créer la transaction
                $transactionData = [
                    'compte_id' => $senderCompte->id,
                    'compte_destinataire_id' => $receiverCompte->id,
                    'type_transaction_id' => $data['type_transaction_id'],
                    'montant' => $montantBase,
                    'montant_debite' => $montantADebiter,
                    'montant_credite' => $montantACrediter,
                    'frais' => false, // Pas de frais pour les paiements
                    'montant_frais' => 0,
                    'frais_paye_par' => null
                ];
    
                $transactions[] = $this->transactionRepository->create($transactionData);
            }
        });
    
        return $transactions;
    }
    public function planifierTransfert(array $data)
{
    // Validation des données
    $this->validatePlanificationData($data);

    // Récupérer l'utilisateur authentifié comme expéditeur
    $userId = auth('api')->user()->id;
    if (!$userId) {
        throw new Exception("Utilisateur non authentifié.");
    }
    $sender = Utilisateur::findOrFail($userId);
    $senderCompte = $sender->comptes()->firstOrFail();
    $existingPlanning = TransactionPlanifiee::where('compte_id', $senderCompte->id)
                        ->where('active', true)
                        ->first();

    if ($existingPlanning) {
        throw new Exception("Vous avez déjà une planification de transfert active. Vous ne pouvez pas en créer une nouvelle tant qu'elle est active.");
    }

    // Calculer les montants et frais
    $montantBase = $data['montant'];
    $frais = $montantBase * self::POURCENTAGE_FRAIS;
    $appliquerFrais = isset($data['frais']) && $data['frais'] === true;
    
    $montantADebiter = $appliquerFrais ? $montantBase + $frais : $montantBase;
    $montantACrediter = $appliquerFrais ? $montantBase : $montantBase - $frais;

    // Calculer le montant total à débiter pour tous les destinataires
    $nombreDestinataires = count($data['receiver_phones']);
    $montantTotalADebiter = $montantADebiter * $nombreDestinataires;

    // Vérifier si le solde est suffisant
    if ($senderCompte->solde < $montantTotalADebiter) {
        throw new Exception("Solde insuffisant pour planifier ces transferts. Montant total nécessaire: " . $montantTotalADebiter);
    }

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
            $repository
        ) {
            foreach ($data['receiver_phones'] as $phoneNumber) {
                // Rechercher l'utilisateur par numéro de téléphone
                $receiverUser = Utilisateur::where('telephone', $phoneNumber)->first();
                if (!$receiverUser) {
                    throw new Exception("Aucun utilisateur trouvé avec le numéro de téléphone: " . $phoneNumber);
                }

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
                    'receiver_id' => $receiverUser->id,
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

private function validatePlanificationData(array $data)
{
    if (!isset($data['type_transaction_id']) || !isset($this->typeTransactions[$data['type_transaction_id']]) || 
        strtoupper($this->typeTransactions[$data['type_transaction_id']]) !== 'TRANSFERT') {
        throw new Exception("Le type de transaction doit être 'TRANSFERT'.");
    }

    if (empty($data['receiver_phones'])) {
        throw new Exception("La liste des numéros de téléphone des destinataires ne peut pas être vide.");
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
        try {
            /** @var TransactionPlanifiee[] $transactionsPlanifiees */
            $transactionsPlanifiees = TransactionPlanifiee::query()
                ->with([
                    'compte',
                    'compte.utilisateur',
                    'compteDestinataire',
                    'compteDestinataire.utilisateur'
                ])
                ->where('active', true)
                ->where('statut', 'PLANIFIE')
                ->where('date_debut', '<=', now())
                ->get();

            foreach ($transactionsPlanifiees as $transactionPlanifiee) {
                DB::beginTransaction();

                try {
                    // Vérifier que tous les éléments nécessaires existent
                    if (!$this->verifierDonneesPlanification($transactionPlanifiee)) {
                        Log::error("Données manquantes pour le transfert planifié #{$transactionPlanifiee->id}");
                        $transactionPlanifiee->update([
                            'statut' => 'ERREUR',
                            'active' => false
                        ]);
                        DB::commit();
                        continue;
                    }

                    // Préparer les données pour le transfert
                    $data = $this->preparerDonneesTransfert($transactionPlanifiee);

                    // Effectuer le transfert
                    $this->effectuerTransaction($data);

                    // Mettre à jour la planification
                    $this->mettreAJourPlanification($transactionPlanifiee);

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error("Erreur lors de l'exécution du transfert planifié #{$transactionPlanifiee->id}: " . $e->getMessage());
                    $transactionPlanifiee->update(['statut' => 'ERREUR']);
                }
            }
        } catch (Exception $e) {
            Log::error("Erreur globale lors de l'exécution des transferts planifiés: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Vérifie que toutes les données nécessaires sont présentes
     * 
     * @param TransactionPlanifiee $transactionPlanifiee
     * @return bool
     */
    private function verifierDonneesPlanification(TransactionPlanifiee $transactionPlanifiee): bool
    {
        return $transactionPlanifiee->compte !== null
            && $transactionPlanifiee->compte->utilisateur !== null
            && $transactionPlanifiee->compteDestinataire !== null
            && $transactionPlanifiee->compteDestinataire->utilisateur !== null;
    }

    /**
     * Prépare les données pour le transfert
     * 
     * @param TransactionPlanifiee $transactionPlanifiee
     * @return array
     */
    private function preparerDonneesTransfert(TransactionPlanifiee $transactionPlanifiee): array
    {
        return [
            'type_transaction_id' => $transactionPlanifiee->type_transaction_id,
            'compte_id' => $transactionPlanifiee->compte_id,
            'montant' => $transactionPlanifiee->montant,
            'frais' => $transactionPlanifiee->frais,
            'receiver_phones' => [
                $transactionPlanifiee->compteDestinataire->utilisateur->telephone
            ]
        ];
    }

    /**
     * Met à jour la planification après exécution
     * 
     * @param TransactionPlanifiee $transactionPlanifiee
     * @return void
     */
    private function mettreAJourPlanification(TransactionPlanifiee $transactionPlanifiee): void
    {
        $prochaineDateExecution = $this->calculerProchaineDateExecution($transactionPlanifiee);

        if ($prochaineDateExecution === null) {
            // C'était la dernière exécution
            $transactionPlanifiee->update([
                'active' => false,
                'statut' => 'TERMINE'
            ]);
        } else {
            // Mettre à jour la date de la prochaine exécution
            $transactionPlanifiee->update([
                'date_debut' => $prochaineDateExecution,
                'statut' => 'PLANIFIE'
            ]);
        }
    }

    /**
     * Calcule la prochaine date d'exécution
     * 
     * @param TransactionPlanifiee $transactionPlanifiee
     * @return Carbon|null
     */
    private function calculerProchaineDateExecution(TransactionPlanifiee $transactionPlanifiee): ?Carbon
    {
        $dateActuelle = Carbon::now();
        $dateDebut = Carbon::parse($transactionPlanifiee->date_debut);

        switch ($transactionPlanifiee->frequence) {
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

        return $nouvelleDate ?? null;
    }
    public function obtenirToutesTransactions()
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                throw new Exception("Utilisateur non authentifié.");
            }
    
            $utilisateur = Utilisateur::find($user->id);
            if (!$utilisateur) {
                throw new Exception("Utilisateur non trouvé.");
            }
    
            $compte = $utilisateur->comptes()->first();
            if (!$compte) {
                throw new Exception("Aucun compte trouvé pour cet utilisateur.");
            }
    
            $transactions = Transaction::with(['typeTransaction', 'senderCompte.utilisateur', 'receiverCompte.utilisateur'])
            ->where(function ($query) use ($compte) {
                $query->where('compte_id', $compte->id)
                      ->orWhere('compte_destinataire_id', $compte->id);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) use ($compte) {
                $data = [
                    'id' => $transaction->id,
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                    'montant' => $transaction->montant,
                    'type' => $transaction->typeTransaction->nom,
                    'description' => ''
                ];
        
                if ($transaction->senderCompte && $transaction->senderCompte->utilisateur) {
                    $data['sender_name'] = $transaction->senderCompte->utilisateur->prenom . ' ' . $transaction->senderCompte->utilisateur->nom;
                }
        
                if ($transaction->receiverCompte && $transaction->receiverCompte->utilisateur) {
                    $data['receiver_name'] = $transaction->receiverCompte->utilisateur->prenom . ' ' . $transaction->receiverCompte->utilisateur->nom;
                } else {
                    $data['receiver_name'] = 'Non spécifié';
                }
        
                if ($transaction->typeTransaction->nom === 'TRANSFERT') {
                    if ($transaction->compte_id === $compte->id) {
                        $data['description'] = 'Transfert envoyé';
                        $data['montant'] = -$transaction->montant;
                    } elseif ($transaction->compte_destinataire_id === $compte->id) {
                        $data['description'] = 'Transfert reçu';
                    }
                } elseif ($transaction->typeTransaction->nom === 'DEPOT') {
                    $data['description'] = 'Dépôt sur le compte';
                } elseif ($transaction->typeTransaction->nom === 'RETRAIT') {
                    $data['description'] = 'Retrait du compte';
                    $data['montant'] = -$transaction->montant;
                } elseif ($transaction->typeTransaction->nom === 'PAYEMENT') {  // Correction ici aussi
                    if ($transaction->compte_id === $compte->id) {
                        $data['description'] = 'Payement effectué';
                        $data['montant'] = -$transaction->montant;
                    } elseif ($transaction->compte_destinataire_id === $compte->id) {
                        $data['description'] = 'Payement reçu';
                    }
                } else {
                    $data['description'] = ucfirst(strtolower($transaction->typeTransaction->nom));
                }
        
                return $data;
            });
        
            return $transactions;
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la récupération des transactions : " . $e->getMessage());
        }
    }

    public function effectuerTransfertPlanifie(array $data)
    {
        DB::beginTransaction();
    
        try {
            if (!isset($data['type_transaction_id']) || !isset($this->typeTransactions[$data['type_transaction_id']])) {
                throw new Exception("Type de transaction invalide.");
            }
    
            $typeTransactionNom = $this->typeTransactions[$data['type_transaction_id']];
            if (strtoupper($typeTransactionNom) !== 'TRANSFERT') {
                throw new Exception("Cette méthode ne gère que les transferts.");
            }
    
            // Vérification des données requises
            if (!isset($data['compte_id']) || !isset($data['receiver_phones'])) {
                throw new Exception("Données manquantes pour le transfert.");
            }
    
            // Récupérer le compte émetteur
            $compteEmetteur = Compte::with('utilisateur')->find($data['compte_id']);
            if (!$compteEmetteur) {
                throw new Exception("Compte émetteur introuvable.");
            }
    
            $montantBase = $data['montant'];
            $frais = $montantBase * self::POURCENTAGE_FRAIS;
            $appliquerFrais = isset($data['frais']) && $data['frais'] === true;
    
            if ($appliquerFrais) {
                $montantADebiter = $montantBase + $frais;
                $montantACrediter = $montantBase;
            } else {
                $montantADebiter = $montantBase;
                $montantACrediter = $montantBase - $frais;
            }
    
            // Vérifier le solde
            if ($compteEmetteur->solde < $montantADebiter) {
                throw new Exception("Solde insuffisant pour effectuer ce transfert.");
            }
    
            $transactions = [];
    
            foreach ($data['receiver_phones'] as $phoneNumber) {
                // Rechercher l'utilisateur destinataire
                $receiverUser = Utilisateur::where('telephone', $phoneNumber)->first();
                if (!$receiverUser) {
                    throw new Exception("Aucun utilisateur trouvé avec le numéro de téléphone: " . $phoneNumber);
                }
    
                $receiverCompte = $receiverUser->comptes()->first();
                if (!$receiverCompte) {
                    throw new Exception("Compte destinataire introuvable pour : " . $phoneNumber);
                }
    
                // Débiter le compte émetteur
                $compteEmetteur->solde -= $montantADebiter;
                $compteEmetteur->save();
    
                // Créditer le compte destinataire
                $receiverCompte->solde += $montantACrediter;
                $receiverCompte->save();
    
                // Créer la transaction
                $transactionData = [
                    'compte_id' => $compteEmetteur->id,
                    'compte_destinataire_id' => $receiverCompte->id,
                    'type_transaction_id' => $data['type_transaction_id'],
                    'montant' => $montantBase,
                    'montant_debite' => $montantADebiter,
                    'montant_credite' => $montantACrediter,
                    'frais' => $appliquerFrais,
                    'montant_frais' => $frais,
                    'frais_paye_par' => $appliquerFrais ? 'emetteur' : 'destinataire'
                ];
    
                $transactions[] = $this->transactionRepository->create($transactionData);
            }
    
            DB::commit();
            return $transactions;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Erreur lors du transfert planifié: " . $e->getMessage());
        }
    }
    public function obtenirTransactionParId(int $id) { /* ... */ }
    public function mettreAJourTransaction(int $id, array $data) { /* ... */ }
    public function supprimerTransaction(int $id) { /* ... */ }
}