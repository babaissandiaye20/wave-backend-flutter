<?php

namespace App\Services;

use Exception;
use App\Models\Compte;
use App\Models\Utilisateur;
use App\Services\PDFService;
use App\Services\EmailService;
use App\Services\QRCodeService;
use Illuminate\Http\UploadedFile;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessUtilisateurCreation;
use App\Services\Interfaces\UtilisateurServiceInterface;
use App\Repositories\Interfaces\CompteRepositoryInterface;
use App\Repositories\Interfaces\UtilisateurRepositoryInterface;

class UtilisateurService implements UtilisateurServiceInterface
{
    protected $utilisateurRepository;
    protected $compteRepository;
    protected $cloudinaryService;
    protected $pdfService;
    protected $qrCodeService;
    protected $emailService;

    public function __construct(
        UtilisateurRepositoryInterface $utilisateurRepository,
        CompteRepositoryInterface $compteRepository,
        CloudinaryService $cloudinaryService,
        QRCodeService $qrCodeService,
        PDFService $pdfService,
        EmailService $emailService
    ) {
        $this->utilisateurRepository = $utilisateurRepository;
        $this->compteRepository = $compteRepository;
        $this->cloudinaryService = $cloudinaryService;
        $this->qrCodeService = $qrCodeService;
        $this->pdfService = $pdfService;
        $this->emailService = $emailService;
    }

    public function createUtilisateur(array $data, UploadedFile $photo = null)
    {
        try {
            if (isset($data['role'])) {
                if (!in_array($data['role'], ['client', 'agent', 'marchand', 'admin'])) {
                    throw new Exception('Rôle invalide');
                }
            }
            $data['codesecret'] = Hash::make($data['codesecret']);
    
            if ($photo) {
                $data['photo'] = $this->cloudinaryService->uploadImage($photo, 'utilisateurs');
            }
    
            $utilisateur = $this->utilisateurRepository->create($data);
    
            $compteData = [
                'utilisateur_id' => $utilisateur->id,
                'solde' => 0,
                'plafond_solde' => 1000000,
                'cumul_transaction_mensuelle' => 6000000,
            ];
            $compte = $this->compteRepository->create($compteData);
    
            // Mise en file d'attente du job pour le traitement asynchrone
            ProcessUtilisateurCreation::dispatch($utilisateur, $compte);
    
            return $utilisateur;
    
        } catch (Exception $e) {
            throw new Exception('Erreur lors de la création de l\'utilisateur: ' . $e->getMessage());
        }
    }
    
    public function setUtilisateurAsPlanned(int $id)
    {
        $utilisateur = $this->utilisateurRepository->findById($id);

        if (!$utilisateur) {
            throw new Exception("Utilisateur non trouvé.");
        }

        $utilisateur->markAsPlanned();

        return $utilisateur;
    }
    public function getAllUtilisateursExcept(int $utilisateurId)
{
    return $this->utilisateurRepository->getAllExcept($utilisateurId);
}
public function checkPhoneNumbers(array $telephones): array
{
    return $this->utilisateurRepository->findExistingPhones($telephones);
}


}