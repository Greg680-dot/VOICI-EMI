<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ActorsSeeder extends Seeder
{
    public function run(): void
    {
        $actors = [
            [
                'name' => 'Super Administrateur VoiCI-EMI',
                'email' => 'sa@voici-emi.bj',
                'password' => 'Admin12345!',
                'role' => 'Super Administrateur',
            ],
            [
                'name' => 'Marcelle Agossou (Administratrice)',
                'email' => 'admin@voici-emi.bj',
                'password' => 'Password123!',
                'role' => 'Administrateur',
            ],
            [
                'name' => 'Grégoire Djeton (Rédacteur en Chef)',
                'email' => 'redacteur@voici-emi.bj',
                'password' => 'Password123!',
                'role' => 'Rédacteur',
            ],
            [
                'name' => 'Gérard Houndété (Journaliste Fact-Checker)',
                'email' => 'factchecker@voici-emi.bj',
                'password' => 'Password123!',
                'role' => 'Fact Checker',
            ],
            [
                'name' => 'Aïcha Bio Guéra (Formatrice Académie)',
                'email' => 'formateur@voici-emi.bj',
                'password' => 'Password123!',
                'role' => 'Formateur',
            ],
            [
                'name' => 'Koffi Mensah (Modérateur Communauté)',
                'email' => 'moderateur@voici-emi.bj',
                'password' => 'Password123!',
                'role' => 'Modérateur',
            ],
            [
                'name' => 'Radio Deeman Parakou (Partenaire Média)',
                'email' => 'partenaire@voici-emi.bj',
                'password' => 'Password123!',
                'role' => 'Partenaire',
            ],
            [
                'name' => 'Farida Mama (Apprenante Certifiante)',
                'email' => 'apprenant@voici-emi.bj',
                'password' => 'Password123!',
                'role' => 'Apprenant',
            ],
            [
                'name' => 'Sèdaminou Houessou (Citoyen Engagé)',
                'email' => 'citoyen@voici-emi.bj',
                'password' => 'Password123!',
                'role' => 'Citoyen',
            ],
        ];

        foreach ($actors as $actorData) {
            $user = User::firstOrNew(['email' => $actorData['email']]);
            $user->name = $actorData['name'];
            $user->password = Hash::make($actorData['password']);
            $user->status = 'active';
            $user->email_verified_at = now();
            $user->save();

            // Assign role
            if (Role::where('name', $actorData['role'])->exists()) {
                $user->syncRoles([$actorData['role']]);
            }
        }
    }
}
