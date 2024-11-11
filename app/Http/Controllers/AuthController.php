<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Utilisateur;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'codesecret' => 'required|string',
        ]);

        // Rechercher l'utilisateur soit par login soit par téléphone
        $utilisateur = Utilisateur::where('login', $request->login)
                                  ->orWhere('telephone', $request->login)
                                  ->first();

        // Vérifier si le code secret est correct
        if ($utilisateur && Hash::check($request->codesecret, $utilisateur->codesecret)) {
            // Créer un token d'accès
            $token = $utilisateur->createToken('AccessToken')->accessToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $utilisateur,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Login, téléphone ou code secret incorrect',
        ], 401);
    }
}
