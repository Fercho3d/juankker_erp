<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL', 'super@juankker.com');
        $password = env('SUPERADMIN_PASSWORD', 'Juankker2026');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Superadmin',
                'password' => Hash::make($password),
                'organization_id' => null,
                'is_superadmin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
