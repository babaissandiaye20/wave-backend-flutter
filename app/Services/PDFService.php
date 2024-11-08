<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Exception;
use Illuminate\Support\Facades\Storage;

class PDFService
{
    public function generatePDF($htmlContent, $filePath, $paperSize = 'A4', $orientation = 'portrait')
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', storage_path('app/public'));
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($htmlContent);
        $dompdf->setPaper($paperSize, $orientation);
        $dompdf->render();

        if (!Storage::disk('public')->exists('cartes')) {
            Storage::disk('public')->makeDirectory('cartes');
        }

        Storage::disk('public')->put($filePath, $dompdf->output());
        
        return $filePath;
    }

    public function generateMembershipCard($utilisateur, $compte, $qrCodeData)
    {
        $userPhotoUrl = $utilisateur->photo;
        $qrCodeBase64 = $qrCodeData['base64'];
        
        $userName = $utilisateur->nom . ' ' . $utilisateur->prenom;
        $login = $utilisateur->login;
        $accountBalance = $compte->solde;
        $accountLimit = $compte->plafond_solde;
        $monthlyTransaction = $compte->cumul_transaction_mensuelle;

        $htmlContent = "
        <!DOCTYPE html>
        <html>
            <head>
                <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .card { 
                        width: 90%; 
                        max-width: 400px; 
                        margin: 0 auto;
                        padding: 20px; 
                        border: 1px solid #ddd; 
                        border-radius: 10px; 
                    }
                    .header { text-align: center; margin-bottom: 20px; }
                    .header img { 
                        border-radius: 50%; 
                        width: 100px; 
                        height: 100px; 
                        object-fit: cover; 
                    }
                    .content { margin-bottom: 20px; }
                    .qr-code { text-align: center; }
                    .qr-code img { width: 150px; height: 150px; }
                </style>
            </head>
            <body>
                <div class='card'>
                    <div class='header'>
                        <img src='$userPhotoUrl' alt='Photo utilisateur'>
                        <h2>$userName</h2>
                        <p>Login: $login</p>
                    </div>
                    <div class='content'>
                        <p><strong>Solde:</strong> $accountBalance FCFA</p>
                        <p><strong>Plafond:</strong> $accountLimit FCFA</p>
                        <p><strong>Cumul mensuel:</strong> $monthlyTransaction FCFA</p>
                    </div>
                    <div class='qr-code'>
                        <img src='data:image/png;base64,$qrCodeBase64' alt='QR Code'>
                    </div>
                </div>
            </body>
        </html>";

        $pdfPath = 'cartes/' . $utilisateur->login . '_carte_membre.pdf';
        return $this->generatePDF($htmlContent, $pdfPath, 'A4', 'portrait');
    }
}