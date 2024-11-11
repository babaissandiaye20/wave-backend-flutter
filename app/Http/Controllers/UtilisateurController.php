<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Interfaces\UtilisateurRepositoryInterface;
use App\Services\Interfaces\UtilisateurServiceInterface;

class UtilisateurController extends Controller
{
    protected $utilisateurService;
    protected $utilisateurRepository;

    public function __construct(UtilisateurServiceInterface $utilisateurService,UtilisateurRepositoryInterface $utilisateurRepository)
    {
        $this->utilisateurService = $utilisateurService;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    /**
     * Crée un nouvel utilisateur.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
{
    Log::info('Données reçues:', [
        'all' => $request->all(),
        'telephone' => $request->input('telephone'),
        'headers' => $request->headers->all()
    ]);

    // Correction du merge pour le téléphone
    $request->merge(['nom' => $request->input('nom') ?? $request->input('nom_')]);
    $request->merge(['telephone' => $request->input('telephone_')]);
    
    // Validation
    $this->validate($request, [
        'prenom' => 'required|string|max:255',
        'nom' => 'required|string|max:255',
        'login' => 'required|string|unique:utilisateurs,login|max:255',
        'codesecret' => 'required|string|min:6',
        'role' => 'nullable|in:client,agent,admin',
        'photo' => 'nullable|image|max:2048',
        'telephone' => 'required|string|unique:utilisateurs,telephone|regex:/^[0-9]{9}$/', // Format sénégalais
    ]);
    
    $data = $request->only(['prenom', 'nom', 'login', 'codesecret', 'role', 'telephone']);
    $photo = $request->file('photo');
    
    try {
        $utilisateur = $this->utilisateurService->createUtilisateur($data, $photo);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès.',
            'data' => $utilisateur,
        ], 201);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
    public function markAsPlanned(int $id): JsonResponse
    {
        try {
            $utilisateur = $this->utilisateurService->setUtilisateurAsPlanned($id);

            return response()->json([
                'success' => true,
                'message' => 'Utilisateur marqué comme planifié.',
                'data' => $utilisateur,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
    public function index(): JsonResponse
{
    try {
        // Obtenir l'ID de l'utilisateur connecté
        $utilisateurConnecteId = Auth::id();

        // Récupérer tous les utilisateurs sauf celui connecté
        $utilisateurs = $this->utilisateurService->getAllUtilisateursExcept($utilisateurConnecteId);

        return response()->json([
            'success' => true,
            'data' => $utilisateurs,
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
public function checkIfPhoneExists(Request $request): JsonResponse
{
    try {
        // Récupérer le numéro de téléphone ou le tableau de numéros
        $telephones = $request->input('telephone');

        // S'assurer que les données sont au format tableau
        $telephones = is_array($telephones) ? $telephones : [$telephones];

        // Appel direct au repository pour vérifier les numéros existants
        $existingPhones = $this->utilisateurRepository->findExistingPhones($telephones);

        return response()->json([
            'success' => true,
            'existingPhones' => $existingPhones,
        ], 200);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}
/**
 * Affiche les informations du compte de l'utilisateur connecté via Passport.
 *
 * @return JsonResponse
 */
public function getCompteInfo(): JsonResponse
{
    try {
        // Récupérer l'utilisateur connecté via le token Passport
        $utilisateurConnecte = auth('api')->user();

        if (!$utilisateurConnecte) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.',
            ], 401);
        }

        // Récupérer l'ID de l'utilisateur connecté
        $utilisateurId = $utilisateurConnecte->id;

        // Rechercher le compte associé à cet utilisateur
        $compte = $this->utilisateurRepository->getCompteByUtilisateurId($utilisateurId);

        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé pour cet utilisateur.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $compte,
        ], 200);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


}
