<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compte extends Model
{
    use HasFactory;

    protected $fillable = [
        'utilisateur_id',
        'solde',
        'plafond_solde',
        'cumul_transaction_mensuelle',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];


    /**
     * Relation avec le modèle Utilisateur
     */
    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class);
    }

    /**
     * Relation avec les transactions associées
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
