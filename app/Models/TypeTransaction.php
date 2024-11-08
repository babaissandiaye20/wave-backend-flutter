<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeTransaction extends Model
{
    use HasFactory;

    protected $table = 'type_transactions';

    protected $fillable = [
        'nom',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Relation avec les transactions associées
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
