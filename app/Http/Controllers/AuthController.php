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

        // Trouver l'utilisateur par login
        $utilisateur = Utilisateur::where('login', $request->login)->first();

        // Vérifier si le code secret est correct
        if ($utilisateur && Hash::check($request->codesecret, $utilisateur->codesecret)) {
            // Créer un token avec Passport
            $token = $utilisateur->createToken('AccessToken')->accessToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $utilisateur,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Login ou code secret incorrect',
        ], 401);
    }
}
