<?php

namespace App\Services;

use App\Repositories\Interfaces\TypeTransactionRepositoryInterface;
use App\Services\Interfaces\TypeTransactionServiceInterface;

class TypeTransactionService implements TypeTransactionServiceInterface
{
    protected $typeTransactionRepository;

    public function __construct(TypeTransactionRepositoryInterface $typeTransactionRepository)
    {
        $this->typeTransactionRepository = $typeTransactionRepository;
    }

    public function getAllTypes()
    {
        return $this->typeTransactionRepository->findAll();
    }

    public function getTypeById(int $id)
    {
        return $this->typeTransactionRepository->findById($id);
    }

    public function createType(array $data)
    {
        return $this->typeTransactionRepository->create($data);
    }

    public function updateType(int $id, array $data)
    {
        return $this->typeTransactionRepository->update($id, $data);
    }

    public function deleteType(int $id)
    {
        return $this->typeTransactionRepository->delete($id);
    }
}
