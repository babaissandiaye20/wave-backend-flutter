<?php
namespace App\Jobs;

use Exception;
use App\Models\Utilisateur;
use App\Services\PDFService;
use App\Services\EmailService;
use App\Services\QRCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUtilisateurCreation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $utilisateur;
    protected $compte;

    public function __construct(Utilisateur $utilisateur, $compte)
    {
        $this->utilisateur = $utilisateur;
        $this->compte = $compte;
    }

    public function handle(QRCodeService $qrCodeService, PDFService $pdfService, EmailService $emailService)
    {
        try {
            // Générer le QR code
            $qrCodeData = [
                'utilisateur' => [
                    'nom' => $this->utilisateur->nom,
                    'prenom' => $this->utilisateur->prenom,
                    'login' => $this->utilisateur->login,
                ],
                'compte' => [
                    'solde' => $this->compte->solde,
                    'plafond_solde' => $this->compte->plafond_solde,
                    'cumul_transaction_mensuelle' => $this->compte->cumul_transaction_mensuelle,
                ]
            ];
            $qrCodeResult = $qrCodeService->generateQRCode($qrCodeData, 'qrcodes');

            // Générer le PDF avec le QR code
            $pdfPath = $pdfService->generateMembershipCard($this->utilisateur, $this->compte, $qrCodeResult);

            // Envoyer l'email avec le PDF
            $emailService->sendMembershipCard($this->utilisateur, $this->compte, $pdfPath);

        } catch (Exception $e) {
            Log::error('Erreur lors du traitement du job : ' . $e->getMessage());

            // Nettoyage en cas d'erreur
            if (isset($qrCodeResult['path']) && Storage::disk('public')->exists($qrCodeResult['path'])) {
                Storage::disk('public')->delete($qrCodeResult['path']);
            }
            if (isset($pdfPath) && Storage::disk('public')->exists($pdfPath)) {
                Storage::disk('public')->delete($pdfPath);
            }
            
            throw $e;
        }
    }
}
