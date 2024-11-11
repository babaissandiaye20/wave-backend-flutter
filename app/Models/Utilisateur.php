<?php

namespace App\Models;

use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable; // Importation correcte

class Utilisateur extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'prenom',
        'nom',
        'login',
        'codesecret',
        'role',
        'photo',
        'planifiée',
        'telephone'
    ];

    // Masquer les colonnes created_at et updated_at
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Relation avec les comptes de l'utilisateur.
     */
    public function comptes(): HasMany
    {
        return $this->hasMany(Compte::class);
    }

    public function markAsPlanned()
    {
        $this->planifiée = true;
        return $this->save();
    }
}
