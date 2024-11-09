<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPlanifiee extends Model
{
    use HasFactory;

    protected $table = 'transactions_planifiees';

    protected $fillable = [
        'compte_id',
        'compte_destinataire_id',
        'type_transaction_id',
        'montant',
        'montant_debite',
        'montant_credite',
        'frais',
        'montant_frais',
        'frais_paye_par',
        'receiver_id',
        'date_debut',  // au lieu de date_planification
        'frequence',
        'active',
        'statut'
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'frais' => 'boolean',
        'active' => 'boolean'
    ];

    /**
     * Relation avec le modèle Compte (compte source).
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    /**
     * Relation avec le modèle Compte (compte destinataire).
     */
    public function compteDestinataire()
    {
        return $this->belongsTo(Compte::class, 'compte_destinataire_id');
    }

    /**
     * Relation avec le modèle TypeTransaction.
     */
    public function typeTransaction()
    {
        return $this->belongsTo(TypeTransaction::class, 'type_transaction_id');
    }

    /**
     * Vérifie si la transaction planifiée est active.
     */
    public function isActive()
    {
        return $this->active;
    }
}
