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
    // 🟢 FONCTION SÉCURISÉE POUR L'ID DE LA PROPRIÉTÉ
    // ========================================================
    private function getProprieteId(Request $request)
    {
        if ($request->has('propriete_id') && !empty($request->propriete_id)) {
            return $request->propriete_id;
        }
        $userId = 1; 
        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $link = DB::table('user_as_owner')->where('user_id', $userId)->first();
        return $link ? $link->$propOwnerCol : null;
    }

    // ==========================================
    // 1. PAGE PRINCIPALE (Statistiques & Dossier racine)
    // ==========================================
    public function chargerDossierPrincipal(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $basePath = "proprietes/{$sp_id}";
        
        // Creyi l-dossier ila ma-kanch
        if (!Storage::disk('public')->exists($basePath)) {
            Storage::disk('public')->makeDirectory($basePath);
        }

        try {
            $allFiles = Storage::disk('public')->allFiles($basePath);
            
            // 🟢 1. Taille totale
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

            // 🟢 2. Les 3 fichiers les plus récents
            $recentFiles = collect($filesData)
                ->sortByDesc('last_modified')
                ->take(3)
                ->map(function($f) {
                    $f['size_formatted'] = $this->formatBytes($f['size']);
                    $f['date_formatted'] = \Carbon\Carbon::createFromTimestamp($f['last_modified'])->diffForHumans();
                    return $f;
                })->values();

            // 🟢 3. Calcul par catégorie (Statistiques)
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

            // Formater l-tailles dyal les stats
            foreach ($stats as $cat => $data) {
                $stats[$cat]['size_formatted'] = $this->formatBytes($data['size']);
            }

            // 🟢 4. Contenu du dossier racine
            $rootContent = $this->getFolderContent($basePath);

            return response()->json([
                'success' => true,
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

    // ==========================================
    // 2. ACCÈS À UN SOUS-DOSSIER
    // ==========================================
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

    // ==========================================
    // 3. TÉLÉCHARGER (Fichier ou Dossier en Zip)
    // ==========================================
    public function telecharger(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        $request->validate(['path' => 'required|string']);

        $targetPath = trim($request->path, '/');

        // Sécurité: T2kked blli khddam ghir f l-propriété dyalo
        if (!Str::startsWith($targetPath, "proprietes/{$sp_id}")) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        if (!Storage::disk('public')->exists($targetPath)) {
            return response()->json(['success' => false, 'message' => 'Fichier ou dossier introuvable.'], 404);
        }

        $fullPath = Storage::disk('public')->path($targetPath);

        // Si c'est un dossier -> ZIP
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

        // Si c'est un fichier direct
        return response()->download($fullPath);
    }

    // ==========================================
    // 4. SUPPRIMER (Fichier ou Dossier)
    // ==========================================
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

    // ==========================================
    // 5. RECHERCHER UN FICHIER
    // ==========================================
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

    // ==========================================
    // FONCTIONS D'AIDE (Helpers)
    // ==========================================
    private function getFolderContent($folderPath)
    {
        $directories = Storage::disk('public')->directories($folderPath);
        $files = Storage::disk('public')->files($folderPath);

        $content = [];

        // Traiter les dossiers
        foreach ($directories as $dir) {
            // N7esbou taille dyal dossier kamel
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

        // Traiter les fichiers
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