<?php

namespace App\Repositories\Interfaces;

interface TransactionPlanifieeRepositoryInterface
{
    public function findById(int $id);
    public function findAll();
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    
}
