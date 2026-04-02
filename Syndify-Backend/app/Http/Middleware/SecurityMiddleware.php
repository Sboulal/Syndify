<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');
        $source = $request->header('X-Source');
        
        $secretKey = env('APP_AUTH_KEY', 'SyndifySecretKey123'); // A ajouter f .env

        // 1. Vérifier si les headers kynin
        if (!$timestamp || !$signature || !$source) {
            return response()->json(['status' => 401, 'message' => 'Headers de sécurité manquants.'], 401);
        }

        // 2. Vérification du Timestamp (10 secondes max)
        if (abs(time() - $timestamp) > 10) {
            return response()->json(['status' => 401, 'message' => 'Requête expirée (Timestamp invalide).'], 401);
        }

        // 3. Validation de la signature HMAC SHA-256
        $expectedSignature = hash_hmac('sha256', $timestamp, $secretKey);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json(['status' => 401, 'message' => 'Signature non autorisée.'], 401);
        }

        return $next($request);
    }
}