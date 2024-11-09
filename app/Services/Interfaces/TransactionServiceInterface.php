<?php

namespace App\Services\Interfaces;

interface TransactionServiceInterface
{
    public function effectuerTransaction(array $data);
    public function obtenirToutesTransactions();
    public function obtenirTransactionParId(int $id);
    public function mettreAJourTransaction(int $id, array $data);
    public function supprimerTransaction(int $id);
    public function planifierTransfert(array $data);
}
