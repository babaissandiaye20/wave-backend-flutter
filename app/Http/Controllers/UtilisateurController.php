<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\UtilisateurServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class UtilisateurController extends Controller
{
    protected $utilisateurService;

    public function __construct(UtilisateurServiceInterface $utilisateurService)
    {
        $this->utilisateurService = $utilisateurService;
    }

    /**
     * Crée un nouvel utilisateur.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge(['nom' => $request->input('nom_')]);

        $this->validate($request, [
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'login' => 'required|string|unique:utilisateurs,login|max:255',
            'codesecret' => 'required|string|min:6',
            'role' => 'nullable|in:client,agent,admin',
            'photo' => 'nullable|image|max:2048', // Validation de l'image
        ]);

        $data = $request->only(['prenom', 'nom', 'login', 'codesecret', 'role']);
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
}
