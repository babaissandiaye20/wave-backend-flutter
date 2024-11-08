<?php

namespace App\Repositories;

use App\Models\Compte;
use App\Repositories\Interfaces\CompteRepositoryInterface;

class CompteRepository implements CompteRepositoryInterface
{
    public function findById(int $id)
    {
        return Compte::find($id);
    }

    public function findAll()
    {
        return Compte::all();
    }

    public function create(array $data)
    {
        return Compte::create($data);
    }

    public function update(int $id, array $data)
    {
        $compte = Compte::find($id);
        if ($compte) {
            $compte->update($data);
            return $compte;
        }
        return null;
    }

    public function delete(int $id)
    {
        return Compte::destroy($id);
    }
}
