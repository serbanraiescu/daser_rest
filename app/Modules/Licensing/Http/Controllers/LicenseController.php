<?php

namespace App\Modules\Licensing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Licensing\Services\LicenseService;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function __construct(protected LicenseService $service) {}

    public function show()
    {
        $status = $this->service->getStatus();
        $fingerprint = request()->getHost(); 
        
        // If already active, redirect to admin or home
        if ($status && $status->status === 'active') {
             return redirect('/admin');
        }

        return view('licensing.activate', compact('status', 'fingerprint'));
    }

    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|min:10',
        ]);

        $result = $this->service->verify($request->license_key, force: true);

        if ($result['status'] === 'active' || ($result['status'] === 'grace_period' ?? false)) {
            return redirect('/admin')->with('success', 'License activated successfully!');
        }

        return back()->withErrors(['license_key' => $result['message'] ?? 'Activation failed.']);
    }
}
