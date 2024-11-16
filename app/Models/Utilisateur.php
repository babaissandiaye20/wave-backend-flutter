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
    const ROLE_CLIENT = 'client';
    const ROLE_AGENT = 'agent';
    const ROLE_MARCHAND = 'marchand';
    const ROLE_ADMIN = 'admin';

    protected $attributes = [
        'role' => self::ROLE_CLIENT
    ];

    // Ajoutez cette méthode pour valider les rôles
    public static function roles(): array
    {
        return [
            self::ROLE_CLIENT,
            self::ROLE_AGENT, 
            self::ROLE_MARCHAND,
            self::ROLE_ADMIN
        ];
    }
}
