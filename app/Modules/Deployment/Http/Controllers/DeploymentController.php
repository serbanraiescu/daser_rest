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
                'optimize:clear',
                'storage:link',
                'config:cache',
                'route:cache',
                'view:cache',
                'queue:restart',
            ];

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

            Log::info('Deploy actions ran successfully.', ['ip' => $request->ip()]);

            return response()->json([
                'status' => 'success',
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
                'storage:link',
                'config:cache',
                'route:cache',
                'view:cache',
                'queue:restart',
            ];

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

            Log::info('Fresh deploy actions ran successfully.', ['ip' => $request->ip()]);

            return response()->json([
                'status' => 'success',
                'message' => 'Database completely wiped and recreated successfully!',
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
}
