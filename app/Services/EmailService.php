<?php

namespace App\Services;


use Exception;
use App\Mail\CarteMemberMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendMembershipCard($utilisateur, $compte, $pdfPath)
    {
        try {
            // Vérification que le login existe
            if (empty($utilisateur->login)) {
                throw new Exception('Le login de l\'utilisateur est manquant');
            }

            // Vérification que le fichier PDF existe
            if (!file_exists(storage_path('app/public/' . $pdfPath))) {
                throw new Exception('Le fichier PDF n\'existe pas: ' . $pdfPath);
            }

            // Validation que le login est une adresse email valide
            if (!filter_var($utilisateur->login, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Le login n\'est pas une adresse email valide: ' . $utilisateur->login);
            }

            // Envoi du mail
            Mail::send(new CarteMemberMail($utilisateur, $compte, $pdfPath));

            return true;
        } catch (Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
            throw new Exception('Erreur lors de l\'envoi de l\'email: ' . $e->getMessage());
        }
    }
}