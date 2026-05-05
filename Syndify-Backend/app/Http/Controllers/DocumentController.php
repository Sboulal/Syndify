<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;
use Exception;

class DocumentController extends Controller
{
    private function getProprieteId(Request $request)
    {
        if ($request->has('propriete_id') && !empty($request->propriete_id)) {
            return $request->propriete_id;
        }

        $userId = $request->header('X-User-Id') ?? auth()->id(); 
        if (!$userId) return null;

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $link = DB::table('user_as_owner')->where('user_id', $userId)->first();
        return $link ? $link->$propOwnerCol : null;
    }

    public function chargerDossierPrincipal(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $basePath = "proprietes/{$sp_id}";
        if (!Storage::disk('public')->exists($basePath)) {
            Storage::disk('public')->makeDirectory($basePath);
        }

        try {
            $propIdCol = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $residence = DB::table('proprietes')->where($propIdCol, $sp_id)->first();

            $allFiles = Storage::disk('public')->allFiles($basePath);
            $totalSizeBytes = 0;
            $filesData = [];

            foreach ($allFiles as $file) {
                $size = Storage::disk('public')->size($file);
                $totalSizeBytes += $size;
                $filesData[] = [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => $size,
                    'last_modified' => Storage::disk('public')->lastModified($file),
                    'type' => 'file'
                ];
            }

            $recentFiles = collect($filesData)->sortByDesc('last_modified')->take(3)->map(function($f) {
                $f['size_formatted'] = $this->formatBytes($f['size']);
                $f['date_formatted'] = \Carbon\Carbon::createFromTimestamp($f['last_modified'])->diffForHumans();
                return $f;
            })->values();

            $stats = [
                'Appels de fonds' => ['size' => 0, 'count' => 0, 'folder' => 'appels_fonds'],
                'Rappels' => ['size' => 0, 'count' => 0, 'folder' => 'reminders'],
                'Encaissements' => ['size' => 0, 'count' => 0, 'folder' => 'encaissements'],
                'Assemblées' => ['size' => 0, 'count' => 0, 'folder' => 'assemblees'],
                'Autres' => ['size' => 0, 'count' => 0, 'folder' => 'autres'],
            ];

            foreach ($allFiles as $file) {
                $size = Storage::disk('public')->size($file);
                $categorized = false;
                foreach ($stats as $cat => $data) {
                    if (Str::contains($file, "/{$data['folder']}/")) {
                        $stats[$cat]['size'] += $size;
                        $stats[$cat]['count']++;
                        $categorized = true;
                        break;
                    }
                }
                if (!$categorized) { $stats['Autres']['size'] += $size; $stats['Autres']['count']++; }
            }

            foreach ($stats as $cat => $data) { $stats[$cat]['size_formatted'] = $this->formatBytes($data['size']); }

            return response()->json([
                'success' => true,
                'residence' => [
                    'nom' => $residence->nom ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Casablanca'
                ],
                'data' => [
                    'total_size' => $this->formatBytes($totalSizeBytes),
                    'total_size_bytes' => $totalSizeBytes,
                    'recent_files' => $recentFiles,
                    'statistics' => $stats,
                    'content' => $this->getFolderContent($basePath)
                ]
            ]);
        } catch (Exception $e) { return response()->json(['success' => false, 'message' => $e->getMessage()], 500); }
    }

    public function accederSousDossier(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        $request->validate(['path' => 'required|string']);
        $targetPath = trim($request->path, '/');

        if (!Storage::disk('public')->exists($targetPath)) return response()->json(['success' => false, 'message' => 'Introuvable.'], 404);

        return response()->json(['success' => true, 'data' => $this->getFolderContent($targetPath)]);
    }

    private function getFolderContent($folderPath)
    {
        $directories = Storage::disk('public')->directories($folderPath);
        $files = Storage::disk('public')->files($folderPath);
        $content = [];

        // 🟢 1. L-Khedma d-les Dossiers (Folders)
        foreach ($directories as $dir) {
            // Kan-7esbou l-7ajm dyal ga3 l-fichiers lli wset l-dossier
            $dirFiles = Storage::disk('public')->allFiles($dir);
            $dirSize = 0;
            foreach ($dirFiles as $f) {
                $dirSize += Storage::disk('public')->size($f);
            }

            $content[] = [
                'type' => 'folder', 
                'name' => basename($dir), 
                'path' => $dir, 
                // 🟢 Rddina l-Taille s-s7i7a
                'size_formatted' => $dirSize > 0 ? $this->formatBytes($dirSize) : '--',
                // 🟢 Rddina l-Date d'ajout s-s7i7a
                'date_formatted' => \Carbon\Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))->diffForHumans()
            ];
        }

        // 🟢 2. L-Khedma d-les Fichiers 3adiyin (Files)
        foreach ($files as $file) {
            $content[] = [
                'type' => 'file', 
                'name' => basename($file), 
                'path' => $file, 
                'size_formatted' => $this->formatBytes(Storage::disk('public')->size($file)),
                'date_formatted' => \Carbon\Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))->diffForHumans()
            ];
        }
        
        return $content;
    }

    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    // ==========================================
    // FONCTION TÉLÉCHARGER (FICHIER AWLA DOSSIER ZIP)
    // ==========================================
    public function telecharger(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        $request->validate(['path' => 'required|string']);

        $targetPath = trim($request->path, '/');

        // Sécurité: T-t2kked blli l-user kay-telechargi ghir mn l-Propriété dyalo
        if (!Str::startsWith($targetPath, "proprietes/{$sp_id}")) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        if (!Storage::disk('public')->exists($targetPath)) {
            return response()->json(['success' => false, 'message' => 'Fichier ou dossier introuvable.'], 404);
        }

        $fullPath = Storage::disk('public')->path($targetPath);

        // 🟢 ILA KAN DOSSIER (FOLDER) -> KAY-SAYEB ZIP
        if (is_dir($fullPath)) {
            $zipFileName = basename($targetPath) . '_' . time() . '.zip';
            $zipFilePath = storage_path("app/public/temp/{$zipFileName}");

            if (!Storage::disk('public')->exists('temp')) {
                Storage::disk('public')->makeDirectory('temp');
            }

            $zip = new ZipArchive;
            if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $files = Storage::disk('public')->allFiles($targetPath);
                foreach ($files as $file) {
                    $relativeName = str_replace($targetPath . '/', '', $file);
                    $zip->addFile(Storage::disk('public')->path($file), $relativeName);
                }
                $zip->close();
            }

            // Kay-ssifet l-ZIP w kay-ms7ou mn l-serveur mn b3d
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        }

        // 🟢 ILA KAN FICHIER 3ADI (PDF, PNG...) -> KAY-SSIFTO NISHAN
        return response()->download($fullPath);
    }
}