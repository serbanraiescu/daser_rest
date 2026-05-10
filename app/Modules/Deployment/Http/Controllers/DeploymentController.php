<?php

namespace App\Modules\Deployment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeploymentController extends Controller
{
    public function run(Request $request)
    {
        $token = config('app.deploy_token', env('DEPLOY_TOKEN'));
        
        if (!$token || $request->input('token') !== $token) {
            abort(403, 'Invalid deploy token.');
        }

        $log = [];

        try {
            $commands = [
                'migrate --force',
                'db:seed --class=AllergenSeeder --force',
                'migrate:allergens',
                'optimize:clear',
                'config:cache',
                'route:cache',
                'view:cache',
                'queue:restart',
            ];

            $this->fixCpanelStorage();

            foreach ($commands as $cmd) {
                $start = microtime(true);
                Artisan::call($cmd);
                $output = Artisan::output();
                $log[] = [
                    'command' => $cmd,
                    'output' => trim($output),
                    'duration' => round(microtime(true) - $start, 4) . 's'
                ];
            }

            // Diagnostics
            $diagnostics = [
                'public_path' => public_path(),
                'base_path' => base_path(),
                'storage_exists' => file_exists(public_path('storage')),
                'storage_is_dir' => is_dir(public_path('storage')),
                'storage_writable' => is_writable(public_path('storage')),
                'settings_dir_exists' => file_exists(public_path('storage/settings')),
                'files_in_settings' => file_exists(public_path('storage/settings')) ? array_slice(scandir(public_path('storage/settings')), 0, 10) : [],
            ];

            Log::info('Deploy actions ran successfully.', ['ip' => $request->ip()]);

            return response()->json([
                'status' => 'success',
                'diagnostics' => $diagnostics,
                'log' => $log,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Deploy failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'log' => $log
            ], 500);
        }
    }

    public function fresh(Request $request)
    {
        $token = config('app.deploy_token', env('DEPLOY_TOKEN'));
        
        if (!$token || $request->input('token') !== $token) {
            abort(403, 'Invalid deploy token.');
        }

        $log = [];

        try {
            $commands = [
                'migrate:fresh --force',
                'optimize:clear',
                'config:cache',
                'route:cache',
                'view:cache',
                'queue:restart',
            ];

            $this->fixCpanelStorage();

            foreach ($commands as $cmd) {
                $start = microtime(true);
                Artisan::call($cmd);
                $output = Artisan::output();
                $log[] = [
                    'command' => $cmd,
                    'output' => trim($output),
                    'duration' => round(microtime(true) - $start, 4) . 's'
                ];
            }

            // Diagnostics
            $diagnostics = [
                'public_path' => public_path(),
                'base_path' => base_path(),
                'storage_exists' => file_exists(public_path('storage')),
                'storage_is_dir' => is_dir(public_path('storage')),
                'storage_writable' => is_writable(public_path('storage')),
                'settings_dir_exists' => file_exists(public_path('storage/settings')),
                'files_in_settings' => file_exists(public_path('storage/settings')) ? array_slice(scandir(public_path('storage/settings')), 0, 10) : [],
            ];

            Log::info('Fresh deploy actions ran successfully.', ['ip' => $request->ip()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Database completely wiped and recreated successfully!',
                'diagnostics' => $diagnostics,
                'log' => $log,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Fresh deploy failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'log' => $log
            ], 500);
        }
    }

    protected function fixCpanelStorage()
    {
        $publicStoragePath = public_path('storage');
        
        // Dacă e link (scurtătură), îl ștergem pentru a face loc unui folder real
        if (file_exists($publicStoragePath) && is_link($publicStoragePath)) {
            @unlink($publicStoragePath);
        }

        // Creăm folderul dacă nu există
        if (!file_exists($publicStoragePath)) {
            @mkdir($publicStoragePath, 0777, true);
        }
        
        // Ne asigurăm că are permisiuni de scriere
        @chmod($publicStoragePath, 0777);

        // Creăm și subfolderele uzuale pentru a fi siguri
        foreach(['products', 'categories', 'settings', 'gallery'] as $sub) {
            $subPath = $publicStoragePath . '/' . $sub;
            if (!file_exists($subPath)) {
                @mkdir($subPath, 0777, true);
            }
            @chmod($subPath, 0777);
        }
    }
}
