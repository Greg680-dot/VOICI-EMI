<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed Spatie roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Test that Super Admin has all access.
     */
    public function test_super_admin_can_access_system_config(): void
    {
        $superAdmin = User::factory()->create([
            'status' => 'active',
        ]);
        $superAdmin->assignRole('Super Administrateur');

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/admin/system/config');

        $response->assertStatus(200);
        $response->assertJsonStructure(['openai_model', 'app_locale']);
    }

    /**
     * Test that standard Citizens cannot access admin config.
     */
    public function test_citizen_cannot_access_system_config(): void
    {
        $citizen = User::factory()->create([
            'status' => 'active',
        ]);
        $citizen->assignRole('Citoyen');

        $response = $this->actingAs($citizen)
            ->getJson('/api/admin/system/config');

        $response->assertStatus(403);
    }

    /**
     * Test that Citizen can submit a fact check.
     */
    public function test_citizen_can_submit_fact_check(): void
    {
        $citizen = User::factory()->create([
            'status' => 'active',
        ]);
        $citizen->assignRole('Citoyen');

        $response = $this->actingAs($citizen)
            ->postJson('/api/fact-checking/submit', [
                'content_type' => 'text',
                'content_value' => 'Une rumeur virale sur le Bénin.'
            ]);

        $response->assertStatus(201);
        $response->assertJsonFragment(['status' => 'submitted']);
    }

    /**
     * Test guest verification quota limits.
     */
    public function test_guest_quota_limit(): void
    {
        // 1st request
        $response1 = $this->postJson('/api/fact-checking/submit', [
            'content_type' => 'text',
            'content_value' => 'Rumeur 1'
        ]);
        $response1->assertStatus(201);

        // 2nd request
        $response2 = $this->postJson('/api/fact-checking/submit', [
            'content_type' => 'text',
            'content_value' => 'Rumeur 2'
        ]);
        $response2->assertStatus(201);

        // 3rd request
        $response3 = $this->postJson('/api/fact-checking/submit', [
            'content_type' => 'text',
            'content_value' => 'Rumeur 3'
        ]);
        $response3->assertStatus(201);

        // 4th request (should exceed quota)
        $response4 = $this->postJson('/api/fact-checking/submit', [
            'content_type' => 'text',
            'content_value' => 'Rumeur 4'
        ]);
        $response4->assertStatus(429);
        $response4->assertJsonFragment([
            'message' => 'Quota de vérification d\'urgence dépassé pour les invités (maximum 3 par jour). Veuillez créer un compte pour utiliser le service sans limite.'
        ]);
    }
}
