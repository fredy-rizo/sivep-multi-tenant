<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\EventType;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Species;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LivestockApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function owner(): User
    {
        return User::where('email', 'dueno@finca.local')->firstOrFail();
    }

    public function test_usuario_puede_iniciar_sesion_y_consultar_su_perfil(): void
    {
        $this->withHeader('Origin', 'http://localhost:5173');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'dueno@finca.local',
            'password' => 'Demo123*',
        ])->assertOk()
            ->assertJsonPath('user.email', 'dueno@finca.local')
            ->assertJsonPath('user.organization.slug', 'finca-la-esperanza');

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.role', 'admin_granja');
    }

    public function test_listado_de_animales_se_limita_a_la_organizacion(): void
    {
        Sanctum::actingAs($this->owner());

        $this->getJson('/api/v1/animals')
            ->assertOk()
            ->assertJsonPath('meta.total', 10);
    }

    public function test_no_se_puede_ver_un_animal_de_otra_organizacion(): void
    {
        $otherOrganization = Organization::create([
            'name' => 'Otra Finca',
            'slug' => 'otra-finca',
            'is_active' => true,
        ]);

        $foreignAnimal = Animal::create([
            'organization_id' => $otherOrganization->id,
            'code' => 'EXT-001',
            'species_id' => Species::firstOrFail()->id,
            'sex' => 'hembra',
            'status' => 'activo',
        ]);

        Sanctum::actingAs($this->owner());

        $this->getJson("/api/v1/animals/{$foreignAnimal->id}")->assertNotFound();
    }

    public function test_se_puede_crear_un_animal_y_registrar_su_peso(): void
    {
        Sanctum::actingAs($this->owner());

        $species = Species::firstOrFail();

        $created = $this->postJson('/api/v1/animals', [
            'code' => 'BOV-TEST-01',
            'name' => 'Animal de prueba',
            'species_id' => $species->id,
            'sex' => 'hembra',
            'status' => 'activo',
        ])->assertCreated()
            ->assertJsonPath('data.code', 'BOV-TEST-01')
            ->json('data');

        $weighingType = EventType::where('code', 'pesaje')->firstOrFail();

        $this->postJson("/api/v1/animals/{$created['id']}/events", [
            'event_type_id' => $weighingType->id,
            'event_date' => now()->toDateString(),
            'weight_kg' => 312.4,
        ])->assertCreated()
            ->assertJsonPath('data.weight_kg', '312.40');

        $this->getJson("/api/v1/animals/{$created['id']}")
            ->assertOk()
            ->assertJsonPath('data.current_weight_kg', '312.40')
            ->assertJsonCount(1, 'monthly_summaries');
    }

    public function test_el_gating_del_plan_bloquea_reportes_en_el_plan_basico(): void
    {
        $owner = $this->owner();
        $basic = Plan::where('code', 'basico')->firstOrFail();

        Subscription::where('organization_id', $owner->organization_id)
            ->update(['plan_id' => $basic->id]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/reports/summary')
            ->assertStatus(402)
            ->assertJsonPath('code', 'FEATURE_NOT_INCLUDED');

        // El núcleo ganadero sigue disponible en el plan básico.
        $this->getJson('/api/v1/animals')->assertOk();
    }

    public function test_operario_no_puede_ver_reportes(): void
    {
        $operator = User::where('email', 'operario@finca.local')->firstOrFail();

        Sanctum::actingAs($operator);

        $this->getJson('/api/v1/reports/summary')->assertForbidden();
    }
}
