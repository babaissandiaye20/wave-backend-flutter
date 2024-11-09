<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\HasApiTokens;
class Utilisateur extends Model
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
    ];

    // Masquer les colonnes created_at et updated_at
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Relation avec les comptes de l'utilisateur.
     */
    public function comptes()
    {
        return $this->hasMany(Compte::class);
    }
    public function markAsPlanned()
    {
        $this->planifiée = true;
        return $this->save();
    }
}
