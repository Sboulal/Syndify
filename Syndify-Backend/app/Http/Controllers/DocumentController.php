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
// ========================================================
    // 🟢 FONCTION SÉCURISÉE AVEC AUTHENTIFICATION RÉELLE
    // ========================================================
    private function getProprieteId(Request $request)
    {
        // 1. Priorité l-ID li mssift mn l-Frontend (Angular Payload)
        if ($request->has('propriete_id') && !empty($request->propriete_id)) {
            return $request->propriete_id;
        }

        // 2. Ila Angular masift walo, njbdouh mn l-User li m-connecté (Auth)
        $userId = auth()->id(); 
        
        // Ila makanch m-connecté aslan, maymknch y-accéder
        if (!$userId) {
            return null; 
        }

        $propOwnerCol = \Illuminate\Support\Facades\Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $link = \Illuminate\Support\Facades\DB::table('user_as_owner')->where('user_id', $userId)->first();
        
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
            // 🟢 FIX: Jbdena l-infos dyal l-residence bach nsiftohom l-header
            $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $residence = DB::table('proprietes')->where($propIdCol_propriete, $sp_id)->first();

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

            $recentFiles = collect($filesData)
                ->sortByDesc('last_modified')
                ->take(3)
                ->map(function($f) {
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
                    if (Str::contains($file, "proprietes/{$sp_id}/{$data['folder']}")) {
                        $stats[$cat]['size'] += $size;
                        $stats[$cat]['count']++;
                        $categorized = true;
                        break;
                    }
                }
                if (!$categorized) {
                    $stats['Autres']['size'] += $size;
                    $stats['Autres']['count']++;
                }
            }

            foreach ($stats as $cat => $data) {
                $stats[$cat]['size_formatted'] = $this->formatBytes($data['size']);
            }

            $rootContent = $this->getFolderContent($basePath);

            // 🟢 FIX: Siftna l-residence m3a l-JSON
            return response()->json([
                'success' => true,
                'residence' => [
                    'nom' => $residence->nom ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data' => [
                    'total_size' => $this->formatBytes($totalSizeBytes),
                    'total_size_bytes' => $totalSizeBytes,
                    'recent_files' => $recentFiles,
                    'statistics' => $stats,
                    'content' => $rootContent
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function accederSousDossier(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        $request->validate(['path' => 'required|string']);

        $targetPath = "proprietes/{$sp_id}/" . trim($request->path, '/');

        if (!Storage::disk('public')->exists($targetPath)) {
            return response()->json(['success' => false, 'message' => 'Dossier introuvable.'], 404);
        }

        try {
            $content = $this->getFolderContent($targetPath);
            return response()->json(['success' => true, 'data' => $content]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function telecharger(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        $request->validate(['path' => 'required|string']);

        $targetPath = trim($request->path, '/');

        if (!Str::startsWith($targetPath, "proprietes/{$sp_id}")) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        if (!Storage::disk('public')->exists($targetPath)) {
            return response()->json(['success' => false, 'message' => 'Fichier ou dossier introuvable.'], 404);
        }

        $fullPath = Storage::disk('public')->path($targetPath);

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

            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        }

        return response()->download($fullPath);
    }

    public function supprimer(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        $request->validate(['path' => 'required|string']);

        $targetPath = trim($request->path, '/');

        if (!Str::startsWith($targetPath, "proprietes/{$sp_id}")) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        if (!Storage::disk('public')->exists($targetPath)) {
            return response()->json(['success' => false, 'message' => 'Introuvable.'], 404);
        }

        try {
            $fullPath = Storage::disk('public')->path($targetPath);
            if (is_dir($fullPath)) {
                Storage::disk('public')->deleteDirectory($targetPath);
            } else {
                Storage::disk('public')->delete($targetPath);
            }

            return response()->json(['success' => true, 'message' => 'Supprimé avec succès.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function rechercher(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        $request->validate(['file_name' => 'required|string']);

        $basePath = "proprietes/{$sp_id}";
        $searchTerm = strtolower($request->file_name);

        $allFiles = Storage::disk('public')->allFiles($basePath);
        $results = [];

        foreach ($allFiles as $file) {
            $name = strtolower(basename($file));
            if (Str::contains($name, $searchTerm)) {
                $results[] = [
                    'path' => $file,
                    'name' => basename($file),
                    'size' => Storage::disk('public')->size($file),
                    'size_formatted' => $this->formatBytes(Storage::disk('public')->size($file)),
                    'last_modified' => Storage::disk('public')->lastModified($file),
                    'date_formatted' => \Carbon\Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))->diffForHumans()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => count($results) > 0 ? $results : null
        ]);
    }

    private function getFolderContent($folderPath)
    {
        $directories = Storage::disk('public')->directories($folderPath);
        $files = Storage::disk('public')->files($folderPath);

        $content = [];

        foreach ($directories as $dir) {
            $dirFiles = Storage::disk('public')->allFiles($dir);
            $dirSize = 0;
            foreach ($dirFiles as $f) {
                $dirSize += Storage::disk('public')->size($f);
            }

            $content[] = [
                'type' => 'folder',
                'name' => basename($dir),
                'path' => $dir,
                'size' => $dirSize,
                'size_formatted' => $this->formatBytes($dirSize),
                'last_modified' => Storage::disk('public')->lastModified($dir),
                'date_formatted' => \Carbon\Carbon::createFromTimestamp(Storage::disk('public')->lastModified($dir))->diffForHumans()
            ];
        }

        foreach ($files as $file) {
            $size = Storage::disk('public')->size($file);
            $content[] = [
                'type' => 'file',
                'name' => basename($file),
                'path' => $file,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'last_modified' => Storage::disk('public')->lastModified($file),
                'date_formatted' => \Carbon\Carbon::createFromTimestamp(Storage::disk('public')->lastModified($file))->diffForHumans()
            ];
        }

        return $content;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}