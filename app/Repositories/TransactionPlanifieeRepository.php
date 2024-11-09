<?php

namespace App\Repositories;

use App\Models\TransactionPlanifiee;
use App\Repositories\Interfaces\TransactionPlanifieeRepositoryInterface;

class TransactionPlanifieeRepository implements TransactionPlanifieeRepositoryInterface
{
    public function findById(int $id)
    {
        return TransactionPlanifiee::find($id);
    }

    public function findAll()
    {
        return TransactionPlanifiee::all();
    }

    public function create(array $data)
    {
        return TransactionPlanifiee::create($data);
    }

    public function update(int $id, array $data)
    {
        $transactionPlanifiee = TransactionPlanifiee::find($id);
        if ($transactionPlanifiee) {
            $transactionPlanifiee->update($data);
            return $transactionPlanifiee;
        }
        return null;
    }

    public function delete(int $id)
    {
        return TransactionPlanifiee::destroy($id);
    }
}
