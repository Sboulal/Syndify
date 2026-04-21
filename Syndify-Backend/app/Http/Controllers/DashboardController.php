<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        $request->validate([
            'sp_identifier' => 'required|string',
            'user_id' => 'required|integer',
            'role' => 'required|string'
        ]);

        $sp_id = $request->sp_identifier;
        $role = strtolower($request->role);
        $user_id = $request->user_id;

        try {
            // 1. Njbdou l-Propriété w l-Exercice courant
            $propriete = DB::table('proprietes')->where('sp_identifier', $sp_id)->first();
            $exercice = DB::table('exercices')
                          ->where('sp_identifier', $sp_id)
                          ->whereIn('status', ['en cours', 'en attente'])
                          ->first();

            if (!$exercice) {
                return response()->json(['success' => false, 'message' => 'Aucun exercice actif trouvé.']);
            }

            // 2. Njbdou l-Budgets Planifiés
            $budgetPrev = DB::table('charges_previsionnelles')
                ->where('se_identifier', $exercice->se_identifier)
                ->where('type', 'planifie')
                ->first();
            
            $budgetTravaux = DB::table('charges_travaux')
                ->where('se_identifier', $exercice->se_identifier)
                ->where('type', 'planifie')
                ->first();

            $totalBudget = ($budgetPrev ? $budgetPrev->budget : 0) + ($budgetTravaux ? $budgetTravaux->budget : 0);
            
            // N7ssbou l-dépenses totales
            $totalDepenses = DB::table('depenses')->where('se_identifier', $exercice->se_identifier)->sum('montant');
            $pourcentage = $totalBudget > 0 ? round(($totalDepenses / $totalBudget) * 100) : 0;

            // 3. Trimestres (Consommation)
            $trimestres = [];
            if ($exercice->start_date) {
                $startDate = Carbon::parse($exercice->start_date);
                $budgetParTrimestre = $totalBudget / 4; 

                for ($i = 0; $i < 4; $i++) {
                    $debutTrimestre = $startDate->copy()->addMonths($i * 3);
                    $finTrimestre = $debutTrimestre->copy()->addMonths(3)->subDay();

                    $depensesTrimestre = DB::table('depenses')
                        ->where('se_identifier', $exercice->se_identifier)
                        ->whereBetween('created_at', [$debutTrimestre, $finTrimestre]) 
                        ->sum('montant');

                    $pourcentageTrimestre = $budgetParTrimestre > 0 ? round(($depensesTrimestre / $budgetParTrimestre) * 100) : 0;

                    $trimestres[] = [
                        'nom' => 'Trimestre ' . ($i + 1),
                        'montant' => number_format($budgetParTrimestre, 0, '.', ' '),
                        'pourcentage' => $pourcentageTrimestre > 100 ? 100 : $pourcentageTrimestre
                    ];
                }
            }

            // 4. Événements récents (Moins de 5 jours)
            $dateLimite = Carbon::now()->subDays(5);
            $evenements = DB::table('assemblees')
                ->where('sp_identifier', $sp_id)
                ->where('date_assemblee', '>=', $dateLimite)
                ->get();

            // 5. Soldes 3la 7ssab l-Rôle
            $soldes = [];
            $totalDu = 0;

            if ($role === 'syndic') {
                $soldesDb = DB::table('user_as_owner')
                    ->join('users', 'user_as_owner.user_id', '=', 'users.id')
                    ->where('user_as_owner.propriete_id', $propriete ? $propriete->id : 1)
                    ->select('users.id', 'users.full_name as nom', 'user_as_owner.solde')
                    ->limit(5)
                    ->get();

                foreach ($soldesDb as $s) {
                    $soldes[] = [
                        'id' => 'COP-' . str_pad($s->id, 4, '0', STR_PAD_LEFT),
                        'nom' => $s->nom ?: 'Sans Nom',
                        'solde' => number_format($s->solde, 2, '.', ' '),
                        'isNegatif' => $s->solde < 0,
                        'action' => $s->solde < 0 ? 'Relancer' : ''
                    ];
                    if ($s->solde < 0) $totalDu += abs($s->solde);
                }
            } else {
                // Propriétaire
                $monSolde = DB::table('user_as_owner')->where('user_id', $user_id)->first();
                if ($monSolde) {
                    $soldes[] = [
                        'id' => 'COP-' . str_pad($user_id, 4, '0', STR_PAD_LEFT),
                        'nom' => 'Mon Compte',
                        'solde' => number_format($monSolde->solde, 2, '.', ' '),
                        'isNegatif' => $monSolde->solde < 0,
                        'action' => $monSolde->solde < 0 ? 'Régulariser' : ''
                    ];
                    $totalDu = $monSolde->solde < 0 ? abs($monSolde->solde) : 0;
                }
            }

            // 6. N-siftou kolchi l-Angular
            return response()->json([
                'success' => true,
                'data' => [
                    'residence' => [
                        'nom' => $propriete ? $propriete->name : 'Résidence',
                        'adresse' => $propriete ? $propriete->address : 'Adresse non définie',
                        'exercice' => date('Y', strtotime($exercice->start_date)),
                        'periode' => $exercice->period ?? 'Trimestre',
                        'gerant' => 'Cabinet Syndic',
                        'role' => ucfirst($role)
                    ],
                    'budget' => [
                        'totalAnnee' => number_format($totalBudget, 0, '.', ' '),
                        'depenses' => number_format($totalDepenses, 0, '.', ' '),
                        'pourcentage' => $pourcentage > 100 ? 100 : $pourcentage
                    ],
                    'trimestres' => $trimestres,
                    'soldes' => $soldes,
                    'totalDu' => number_format($totalDu, 0, '.', ' '),
                    'evenements' => $evenements
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur Dashboard: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }
}