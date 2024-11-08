<?php

namespace App\Repositories;

use App\Models\Utilisateur;
use App\Repositories\Interfaces\UtilisateurRepositoryInterface;

class UtilisateurRepository implements UtilisateurRepositoryInterface
{
    public function findById(int $id)
    {
        return Utilisateur::find($id);
    }

    public function findAll()
    {
        return Utilisateur::all();
    }

    public function create(array $data)
    {
        return Utilisateur::create($data);
    }

    public function update(int $id, array $data)
    {
        $utilisateur = Utilisateur::find($id);
        if ($utilisateur) {
            $utilisateur->update($data);
            return $utilisateur;
        }
        return null;
    }

    public function delete(int $id)
    {
        return Utilisateur::destroy($id);
    }
}
