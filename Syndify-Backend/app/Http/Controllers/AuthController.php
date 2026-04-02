<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // ==========================================
    // 1. Inscription (Register) 
    // ==========================================
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string', // Nqdro n-zidouh
            'agreed_on_terms' => 'required|boolean'
        ]);

        // Vérification conflit
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'status' => 409,
                'message' => "L'adresse e-mail est déjà utilisée."
            ], 409);
        }

        // Génération Identifiant & OTP
        $identifier = 'SU-' . time();
        $otpCode = rand(10000, 99999); 

        $user = User::create([
            'identifier' => $identifier,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => null, // 👈 BLA PASSWORD
            'activation_code' => Hash::make($otpCode),
            'otp_expires_at' => now()->addMinutes(15),
            'agreed_on_terms' => $request->agreed_on_terms,
            'status' => 'En attente d’activation'
        ]);

      
        Log::info("OTP d'inscription pour {$user->email} est : {$otpCode}");

        return response()->json([
            'status' => 201,
            'message' => "Compte créé. Un code OTP a été envoyé.",
            'identifier' => $identifier
        ], 201);
    }

    // ==========================================
    // 2. Demander OTP pour la Connexion (Login)
    // ==========================================
    public function requestLoginOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string' // Yqder ykoun Email wla Phone
        ]);

        $identifier = $request->identifier;

        // N-qelbou 3la l'user b l'email awla b l'numéro d tel
        $user = User::where('email', $identifier)
                    ->orWhere('phone', $identifier)
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => "Aucun compte trouvé avec cet identifiant."
            ], 404);
        }

        // Générer OTP jdid
        $otpCode = rand(10000, 99999);
        $user->activation_code = Hash::make($otpCode);
        $user->otp_expires_at = now()->addMinutes(15);
        $user->save();

        Log::info("OTP de connexion pour {$identifier} est : {$otpCode}");

        return response()->json([
            'status' => 200,
            'message' => "Code OTP envoyé avec succès."
        ], 200);
    }

    // ==========================================
    // 3. Vérification de l'OTP (Pour Register w Login)
    // ==========================================
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string', // Email awla Phone awla SU-ID
            'otp_code' => 'required|numeric'
        ]);

        $identifier = $request->identifier;

        // N-qelbou 3la l'user b SU-ID awla email awla phone
        $user = User::where('identifier', $identifier)
                    ->orWhere('email', $identifier)
                    ->orWhere('phone', $identifier)
                    ->first();

        // Vérification validité w expiration
        if (!$user || !Hash::check($request->otp_code, $user->activation_code) || now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'status' => 400,
                'message' => "Le code OTP est invalide ou a expiré."
            ], 400);
        }

        // Si l'OTP est correct: N-nqiwh w n-activiw l'compte ila kan baqi jdid
        $user->status = 'Actif';
        $user->activation_code = null; 
        $user->save();

        // Générer l'JWT Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => "Authentification réussie.",
            'token' => $token,
            'user' => [
                'identifier' => $user->identifier,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'status' => $user->status
            ]
        ], 200);
    }
}