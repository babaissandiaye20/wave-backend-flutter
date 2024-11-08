<?php

namespace App\Repositories;

use App\Models\TypeTransaction;
use App\Repositories\Interfaces\TypeTransactionRepositoryInterface;

class TypeTransactionRepository implements TypeTransactionRepositoryInterface
{
    public function findById(int $id)
    {
        return TypeTransaction::find($id);
    }

    public function findAll()
    {
        return TypeTransaction::all();
    }

    public function create(array $data)
    {
        return TypeTransaction::create($data);
    }

    public function update(int $id, array $data)
    {
        $typeTransaction = TypeTransaction::find($id);
        if ($typeTransaction) {
            $typeTransaction->update($data);
            return $typeTransaction;
        }
        return null;
    }

    public function delete(int $id)
    {
        return TypeTransaction::destroy($id);
    }
}
