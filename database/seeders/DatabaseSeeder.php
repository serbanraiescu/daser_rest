<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Check if admin exists
        if (!User::where('email', 'admin@daser.ro')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@daser.ro',
                'password' => Hash::make('password'),
            ]);
        }
    }
}
