<?php

namespace App\Repositories\Interfaces;

interface UtilisateurRepositoryInterface
{
    public function findById(int $id);
    public function findAll();
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function getAllExcept(int $utilisateurId);
    public function findExistingPhones(array $telephones): array;
    public function getCompteByUtilisateurId(int $utilisateurId);
}
