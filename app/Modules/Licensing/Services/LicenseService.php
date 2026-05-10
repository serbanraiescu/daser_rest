<?php

namespace App\Modules\Licensing\Services;

use App\Modules\Licensing\Models\LicenseStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LicenseService
{
    protected string $endpoint = 'https://app.daserdesign.ro/api/license/verify';

    public function verify(?string $licenseKey = null, bool $force = false): array
    {
        $status = $this->getStatus();
        $licenseKey = $licenseKey ?? $status?->license_key;

        if (!$licenseKey) {
            return ['status' => 'unverified', 'message' => 'No license key found.'];
        }

        // Bypass for Local Testing
        if ($licenseKey === 'DEV-TEST-KEY') {
             $data = ['status' => 'active', 'message' => 'Development License Active', 'is_grace_period' => false];
             $this->updateStatus($licenseKey, $data, 'local-dev');
             return $data;
        }

        // Cache 
        if (!$force && $status && $status->last_checked_at && $status->last_checked_at->diffInHours(now()) < 12) {
             // Return cached status if verified recently (e.g. within 12h) unless forced
             // But usually we want to verify on boot if cache is stale or just trust DB? 
             // Requirement: "at least once at 12 hours". 
             // So if we are here, we might just return current status if we trust it.
             // But for manual calls, we force.
        }

        $fingerprint = request()->getHost(); 

        try {
            $response = Http::timeout(10)->post($this->endpoint, [
                'license_key' => $licenseKey,
                'fingerprint' => $fingerprint,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->updateStatus($licenseKey, $data, $fingerprint);
                return $data;
            }

            // If server error, handle fallback logic?
            // "Daca serverul de licente nu este accesibil temporar: permite acces... 72h cache"
            if ($status && $status->last_checked_at && $status->last_checked_at->diffInHours(now()) < 72 && $status->status === 'active') {
                 // Remain active but log warning
                 return [
                     'status' => 'active', 
                     'message' => 'Offline validation (Server unreachable).',
                     'is_grace_period' => true // Maybe show warning?
                 ];
            }

            return ['status' => 'denied', 'message' => 'License server unreachable and cache expired.'];

        } catch (\Exception $e) {
            Log::error("License verification failed: " . $e->getMessage());
            
            // Fallback similar to above
            if ($status && $status->last_checked_at && $status->last_checked_at->diffInHours(now()) < 72 && $status->status === 'active') {
                 return [
                     'status' => 'active', 
                     'message' => 'Offline validation (Connection failed).',
                     'is_grace_period' => true
                 ];
            }
            
            return ['status' => 'denied', 'message' => 'Connection error: ' . $e->getMessage()];
        }
    }

    public function getStatus(): ?LicenseStatus
    {
        return LicenseStatus::first();
    }

    protected function updateStatus(string $licenseKey, array $data, string $fingerprint): void
    {
        $status = LicenseStatus::firstOrNew();
        $status->license_key = $licenseKey;
        $status->fingerprint = $fingerprint;
        $status->status = $data['status'] ?? 'denied';
        $status->is_grace_period = $data['is_grace_period'] ?? false;
        $status->message = $data['message'] ?? null;
        $status->last_checked_at = now();
        $status->next_check_at = now()->addHours(12);
        $status->save();
    }
}
