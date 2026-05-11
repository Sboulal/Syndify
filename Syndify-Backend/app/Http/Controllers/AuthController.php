<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; 
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

        // 1. Génération dyal l-Identifiers
        $user_identifier = 'SU-' . time();
        $propriete_id_string = 'SP-' . rand(10000000, 99999999);
        $otpCode = rand(10000, 99999); 

        DB::beginTransaction();
        try {
            // 2. Création dyal l-User
            $user = User::create([
                'identifier' => $user_identifier,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'tel' => $request->phone,
                'activation_code' => Hash::make($otpCode),
                'otp_expires_at' => now()->addMinutes(15),
                'agreed_on_terms' => $request->agreed_on_terms,
                'status' => 'En attente d’activation'
            ]);

            // 3. Création dyal Résidence (Match m3a l-Migration dyalek : id)
            DB::table('proprietes')->insert([
                'id' => $propriete_id_string, 
                'nom' => "Ma Résidence",
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 4. R-rabt f table user_as_owner
            DB::table('user_as_owner')->insert([
                'user_id' => $user->id,
                'propriete_id' => $propriete_id_string, // String ID
                'status' => 1, // Actif
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();

            Log::info("OTP d'inscription pour {$user->email} est : {$otpCode}");

            return response()->json([
                'status' => 201,
                'message' => "Compte créé et résidence initialisée.",
                'identifier' => $user_identifier
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 500, 'message' => "Erreur: " . $e->getMessage()], 500);
        }
    }

    public function requestLoginOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string' 
        ]);

        $identifier = $request->identifier;

        $user = User::where('email', $identifier)
                    ->orWhere('tel', $identifier) 
                    ->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => "Aucun compte trouvé avec cet identifiant."
            ], 404);
        }

        $otpCode = rand(10000, 99999);
        $user->activation_code = Hash::make($otpCode);
        $user->otp_expires_at = now()->addMinutes(15);
        $user->save();

        Log::info("Code OTP de Connexion pour {$identifier} est : {$otpCode}");

        return response()->json([
            'status' => 200,
            'message' => "Un code OTP a été envoyé à votre adresse."
        ], 200);
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string', 
            'otp_code' => 'required|numeric'
        ]);

        $identifier = $request->identifier;

        $user = User::where('identifier', $identifier)
                    ->orWhere('email', $identifier)
                    ->orWhere('tel', $identifier) 
                    ->first();

        // 🟢 R-RADAR:
        Log::info("--- TEST VERIFY OTP ---");
        Log::info("OTP recu mn Angular : " . $request->otp_code);
        Log::info("Wach l-Code s7i7? : " . ($user && Hash::check($request->otp_code, $user->activation_code) ? 'OUI' : 'NON'));
        Log::info("Wach Expiré? : (Expire le: " . ($user ? $user->otp_expires_at : 'N/A') . " | Daba hya: " . now() . ")");

        if (!$user || !Hash::check($request->otp_code, $user->activation_code) || now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'status' => 400,
                'message' => "Le code OTP est invalide ou a expiré."
            ], 400);
        }

        // 🔴 HADI HIYA L-PARTIE LLI KNTI MS7TI:
        $user->status = 'Actif';
        $user->activation_code = null; 
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => "Authentification réussie.",
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'identifier' => $user->identifier,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => 'syndic' 
            ]
        ], 200);
    }

        public function logout(Request $request)
        {
            $request->user()->currentAccessToken()->delete();
    
            return response()->json([
                'status' => 200,
                'message' => "Déconnexion réussie."
            ], 200);
        }

}