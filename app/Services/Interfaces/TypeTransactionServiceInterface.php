<?php

namespace App\Services\Interfaces;

interface TypeTransactionServiceInterface
{
    public function getAllTypes();
    
    public function getTypeById(int $id);
    
    public function createType(array $data);
    
    public function updateType(int $id, array $data);
    
    public function deleteType(int $id);
}
