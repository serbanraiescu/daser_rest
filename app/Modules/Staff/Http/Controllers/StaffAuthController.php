<?php

namespace App\Modules\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Staff\Models\StaffMember;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class StaffAuthController extends Controller
{
    public function login()
    {
        // If already logged in, redirect based on role
        if (Session::has('staff_id')) {
            $staff = StaffMember::find(Session::get('staff_id'));
            if ($staff) {
                return $this->redirectBasedOnRole($staff->role);
            }
        }
        
        $settings = \App\Modules\Settings\Models\CompanySetting::first() ?? new \App\Modules\Settings\Models\CompanySetting();
        return view('staff.login', compact('settings'));
    }

    public function verify(Request $request)
    {
        \Illuminate\Support\Facades\Log::info("DEBUG: StaffAuthController@verify hit");
        \Illuminate\Support\Facades\Log::info("DEBUG: All input: " . json_encode($request->all()));
        
        $pin = $request->input('pin');

        // Allow any staff with matching PIN
        $staffMembers = StaffMember::where('is_active', true)->get();
        $authenticatedStaff = null;
        $matches = [];

        foreach ($staffMembers as $staff) {
            $is_match = Hash::check($pin, $staff->pin_code);
            \Illuminate\Support\Facades\Log::info("Checking staff: {$staff->name} (ID: {$staff->id}). Match: " . ($is_match ? 'YES' : 'NO'));
            
            if ($is_match) {
                $matches[] = $staff;
            }
        }

        if (count($matches) > 1) {
             // If multiple matches, we might need a better way, but for now take the first logic
             // Ideally PINs should be unique.
             \Illuminate\Support\Facades\Log::info("Multiple staff found for PIN $pin");
             $authenticatedStaff = $matches[0];
        } elseif (count($matches) === 1) {
            $authenticatedStaff = $matches[0];
        }

        if ($authenticatedStaff) {
            Session::put('staff_id', $authenticatedStaff->id);
            Session::put('staff_role', $authenticatedStaff->role);
            Session::put('staff_name', $authenticatedStaff->name);
            
            return $this->redirectBasedOnRole($authenticatedStaff->role);
        }

        \Illuminate\Support\Facades\Log::warning("Staff login failure: PIN $pin not found for any active staff.");
        return back()->withErrors(['pin' => "Cod PIN incorect. ($pin)"]);
    }

    public function logout()
    {
        Session::forget(['staff_id', 'staff_role', 'staff_name']);
        return redirect()->route('staff.login');
    }

    private function redirectBasedOnRole($role)
    {
        if ($role === 'kitchen') {
            return redirect()->route('kitchen.index');
        } elseif ($role === 'bar') {
            return redirect()->route('bar.index');
        } elseif ($role === 'waiter') {
            return redirect()->route('waiter.index'); 
        } elseif ($role === 'manager') {
             return redirect()->route('waiter.index');
        }
        
        return redirect()->route('staff.login')->withErrors(['pin' => 'Rol necunoscut: ' . $role]);
    }
}
