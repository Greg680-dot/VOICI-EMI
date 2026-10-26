<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $sa = User::factory()->create([
            'name' => 'Super Administrateur VoiCI-EMI',
            'email' => 'sa@voici-emi.bj',
            'password' => bcrypt('Admin12345!'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $sa->assignRole('Super Administrateur');
    }
}
