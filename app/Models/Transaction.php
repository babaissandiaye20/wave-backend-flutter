<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'compte_id',
        'type_transaction_id',
        'montant',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];


    /**
     * Relation avec le modèle Compte
     */
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }

    public function senderCompte()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    public function receiverCompte()
    {
        return $this->belongsTo(Compte::class, 'compte_destinataire_id');
    }

    public function typeTransaction()
    {
        return $this->belongsTo(TypeTransaction::class, 'type_transaction_id');
    }
}
