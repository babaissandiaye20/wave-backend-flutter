<?php

namespace App\Services\Interfaces;

use Illuminate\Http\UploadedFile;

interface UtilisateurServiceInterface
{
    public function createUtilisateur(array $data, UploadedFile $photo = null);
    public function setUtilisateurAsPlanned(int $id);
    public function getAllUtilisateursExcept(int $utilisateurId);
    public function checkPhoneNumbers(array $telephones);
}
