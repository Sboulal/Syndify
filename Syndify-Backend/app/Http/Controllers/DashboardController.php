<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'role' => 'required|string'
        ]);

        $role = strtolower($request->role);
        $user_id = $request->user_id; 

        try {
            $userPk = Schema::hasColumn('users', 'identifier') ? 'identifier' : 'id';
            $userNameCol = Schema::hasColumn('users', 'full_name') ? 'full_name' : 'name';
            $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
            $propIdCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $exPropCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

            $link = DB::table('user_as_owner')->where('user_id', $user_id)->first();
            if (!$link) {
                return response()->json(['success' => false, 'message' => 'Utilisateur non lié à une résidence.']);
            }
            
            $sp_id = $link->$propOwnerCol; 

            // 1. Njbdou l-Propriété w l-Exercice courant
            $propriete = DB::table('proprietes')->where($propIdCol, $sp_id)->first();
            $exercice = DB::table('exercices')
                          ->where($exPropCol, $sp_id)
                          ->whereIn('status', ['en cours', 'en attente'])
                          ->first();

            if (!$exercice) {
                return response()->json(['success' => false, 'message' => 'Aucun exercice actif trouvé.']);
            }

            // 2. Njbdou l-Budgets 
            $budgetPrev = DB::table('charges_previsionnelles')
                ->where('se_identifier', $exercice->se_identifier)
                ->first();
            
            $budgetTravaux = DB::table('charges_travaux')
                ->where('se_identifier', $exercice->se_identifier)
                ->first();

            $totalBudget = ($budgetPrev ? $budgetPrev->budget : 0) + ($budgetTravaux ? $budgetTravaux->budget : 0);
            
            $totalDepenses = DB::table('depenses')->where('se_identifier', $exercice->se_identifier)->sum('amount');
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
                        ->sum('amount');

                    $pourcentageTrimestre = $budgetParTrimestre > 0 ? round(($depensesTrimestre / $budgetParTrimestre) * 100) : 0;

                    $trimestres[] = [
                        'nom' => 'Trimestre ' . ($i + 1),
                        'montant' => number_format($budgetParTrimestre, 0, '.', ' '),
                        'pourcentage' => $pourcentageTrimestre > 100 ? 100 : $pourcentageTrimestre
                    ];
                }
            }

            // 4. Événements récents 
            $dateLimite = Carbon::now()->subDays(5);
            $evenements = [];
            if (Schema::hasTable('assemblees')) {
                $evenements = DB::table('assemblees')
                    ->where('propriete_id', $sp_id)
                    ->where('date_assemblee', '>=', $dateLimite)
                    ->get();
            }

            // 5. Soldes 3la 7ssab l-Rôle
            $soldes = [];
            $totalDu = 0;

            if ($role === 'syndic') {
                $soldesDb = DB::table('user_as_owner')
                    ->join('users', 'user_as_owner.user_id', '=', 'users.id') 
                    ->where('user_as_owner.' . $propOwnerCol, $sp_id)
                    ->select('users.' . $userPk . ' as display_id', 'users.' . $userNameCol . ' as nom', 'users.email', 'user_as_owner.*')
                    ->limit(5)
                    ->get();

                foreach ($soldesDb as $s) {
                    $soldeTotal = $s->balance_prev ?? $s->solde ?? 0; 
                    
                    // 🟢 FIX 1 HNA: N-7iydou les strings (SU-...) w n-khdmo dima b user_id (Raqm) + Base 845752
                    $visualId = (int)$s->user_id + 845752;
                    $finalId = 'COP-' . str_pad($visualId, 8, '0', STR_PAD_LEFT);

                    $soldes[] = [
                        'id' => $finalId,
                        'nom' => $s->nom ?: $s->email,
                        'solde' => number_format((float)$soldeTotal, 2, '.', ' '),
                        'isNegatif' => $soldeTotal < 0,
                        'action' => $soldeTotal < 0 ? 'Relancer' : ''
                    ];
                    if ($soldeTotal < 0) $totalDu += abs($soldeTotal);
                }
            } 
            else {
                // Propriétaire
                $monSolde = DB::table('user_as_owner')->where('user_id', $user_id)->where($propOwnerCol, $sp_id)->first();
                if ($monSolde) {
                    $soldeTotal = $monSolde->balance_prev ?? $monSolde->solde ?? 0;
                    
                    // 🟢 FIX 2 HNA: Nfs l-Blan l-Propriétaire
                    $visualId = (int)$user_id + 845752;
                    $finalId = 'COP-' . str_pad($visualId, 8, '0', STR_PAD_LEFT);

                    $soldes[] = [
                        'id' => $finalId,
                        'nom' => 'Mon Compte',
                        'solde' => number_format((float)$soldeTotal, 2, '.', ' '),
                        'isNegatif' => $soldeTotal < 0,
                        'action' => $soldeTotal < 0 ? 'Régulariser' : ''
                    ];
                    $totalDu = $soldeTotal < 0 ? abs($soldeTotal) : 0;
                }
            }

            // 6. N-siftou kolchi l-Angular
            return response()->json([
                'success' => true,
                'data' => [
                    'residence' => [
                        'nom' => $propriete->nom ?? 'Résidence',
                        'adresse' => $propriete->address ?? 'Adresse non définie',
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
            return response()->json([
                'success' => false, 
                'message' => 'Erreur s-s7i7a hiya: ' . $e->getMessage()
            ], 500);
        }
    }
}