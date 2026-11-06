<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function owner(): User
    {
        return User::where('email', 'dueno@finca.local')->firstOrFail();
    }

    public function test_administrador_lista_y_resume_los_usuarios_de_su_organizacion(): void
    {
        Sanctum::actingAs($this->owner());

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->getJson('/api/v1/users/summary')
            ->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.active', 3)
            ->assertJsonPath('data.admins', 1)
            ->assertJsonPath('data.field_staff', 2);
    }

    public function test_administrador_crea_un_usuario_con_rol_en_su_organizacion(): void
    {
        $owner = $this->owner();
        Sanctum::actingAs($owner);

        $user = $this->postJson('/api/v1/users', [
            'name' => 'Laura Operaria',
            'email' => 'laura@finca.local',
            'password' => 'Secreta123*',
            'role' => 'operario',
        ])->assertCreated()
            ->assertJsonPath('data.role', 'operario')
            ->assertJsonPath('data.is_active', true)
            ->json('data');

        $this->assertDatabaseHas('users', [
            'id' => $user['id'],
            'organization_id' => $owner->organization_id,
            'email' => 'laura@finca.local',
        ]);

        $this->assertTrue(
            User::findOrFail($user['id'])->hasRole('operario'),
        );
    }

    public function test_no_puede_desactivar_su_propia_cuenta_ni_cambiar_su_rol(): void
    {
        $owner = $this->owner();
        Sanctum::actingAs($owner);

        $this->deleteJson("/api/v1/users/{$owner->id}")
            ->assertStatus(422);

        $this->putJson("/api/v1/users/{$owner->id}", ['role' => 'operario'])
            ->assertStatus(422);
    }

    public function test_no_puede_editar_usuarios_de_otra_organizacion(): void
    {
        $other = Organization::create([
            'name' => 'Finca Ajena',
            'slug' => 'finca-ajena-usuarios',
            'is_active' => true,
        ]);

        $foreign = User::create([
            'name' => 'Usuario Ajeno',
            'email' => 'ajeno@finca.local',
            'password' => Hash::make('Secreta123*'),
            'organization_id' => $other->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->owner());

        $this->putJson("/api/v1/users/{$foreign->id}", ['name' => 'Cambiado'])
            ->assertNotFound();

        $this->assertSame('Usuario Ajeno', $foreign->fresh()->name);
    }

    public function test_validacion_de_correo_duplicado(): void
    {
        Sanctum::actingAs($this->owner());

        $this->postJson('/api/v1/users', [
            'name' => 'Duplicado',
            'email' => 'dueno@finca.local',
            'password' => 'Secreta123*',
            'role' => 'operario',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_plan_basico_bloquea_la_gestion_de_usuarios(): void
    {
        $owner = $this->owner();
        $basic = Plan::where('code', 'basico')->firstOrFail();

        Subscription::where('organization_id', $owner->organization_id)
            ->update(['plan_id' => $basic->id]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/users')
            ->assertStatus(402)
            ->assertJsonPath('code', 'FEATURE_NOT_INCLUDED');
    }

    public function test_operario_no_puede_gestionar_usuarios(): void
    {
        $operator = User::where('email', 'operario@finca.local')->firstOrFail();

        Sanctum::actingAs($operator);

        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_super_admin_opera_sobre_la_organizacion_seleccionada(): void
    {
        $superAdmin = User::where('email', 'admin@sivep.local')->firstOrFail();
        $organization = $this->owner()->organization;

        Sanctum::actingAs($superAdmin);

        $this->getJson('/api/v1/users')
            ->assertStatus(400)
            ->assertJsonPath('code', 'ORGANIZATION_REQUIRED');

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->postJson('/api/v1/users', [
                'name' => 'Soporte',
                'email' => 'soporte@finca.local',
                'password' => 'Secreta123*',
                'role' => 'veterinario',
            ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'soporte@finca.local',
            'organization_id' => $organization->id,
        ]);
    }
}
