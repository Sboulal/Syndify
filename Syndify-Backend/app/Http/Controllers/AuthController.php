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
            'phone' => 'nullable|string', 
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
            'password' => null, 
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
    // 2. Connexion Directe (BYPASS OTP MO2A9ATAN)
    // ==========================================
    public function requestLoginOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string' 
        ]);

        $identifier = $request->identifier;

        $user = User::where('email', $identifier)
                    ->orWhere('phone', $identifier)
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => "Aucun compte trouvé avec cet identifiant."
            ], 404);
        }

        /* 🛑 OTP COMMENTÉ POUR LE TEST 🛑
        $otpCode = rand(10000, 99999);
        $user->activation_code = Hash::make($otpCode);
        $user->otp_expires_at = now()->addMinutes(15);
        */

        // N-activiw l'compte nichan ila kan jdid
        if ($user->status !== 'Actif') {
            $user->status = 'Actif';
            $user->save();
        }

        // 🛑 N-wldou l'Token nichan w n-siftouh l-Angular
        $token = $user->createToken('auth_token')->plainTextToken;

        Log::info("Connexion DIRECTE (Bypass OTP) pour {$identifier}");

        return response()->json([
            'status' => 200,
            'message' => "Authentification réussie (Bypass).",
            'token' => $token, // 👈 Token directement renvoyé
            'user' => [
                'identifier' => $user->identifier,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'status' => $user->status
            ]
        ], 200);
    }

    // ==========================================
    // 3. Vérification de l'OTP (Pour Register)
    // ==========================================
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string', 
            'otp_code' => 'required|numeric'
        ]);

        $identifier = $request->identifier;

        $user = User::where('identifier', $identifier)
                    ->orWhere('email', $identifier)
                    ->orWhere('phone', $identifier)
                    ->first();

        if (!$user || !Hash::check($request->otp_code, $user->activation_code) || now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'status' => 400,
                'message' => "Le code OTP est invalide ou a expiré."
            ], 400);
        }

        $user->status = 'Actif';
        $user->activation_code = null; 
        $user->save();

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