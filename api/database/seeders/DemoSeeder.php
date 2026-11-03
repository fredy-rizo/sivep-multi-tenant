<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Breed;
use App\Models\EventType;
use App\Models\Lot;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Species;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vaccine;
use App\Services\Livestock\AnimalEventService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::updateOrCreate(
            ['slug' => 'finca-la-esperanza'],
            [
                'name' => 'Finca La Esperanza',
                'nit' => '900123456-1',
                'city' => 'Montería',
                'contact_email' => 'contacto@laesperanza.local',
                'contact_phone' => '+57 300 123 4567',
                'is_active' => true,
            ],
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@sivep.local'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('Admin123*'),
                'organization_id' => null,
                'is_active' => true,
            ],
        );
        $superAdmin->syncRoles(['super_admin']);

        $owner = User::updateOrCreate(
            ['email' => 'dueno@finca.local'],
            [
                'name' => 'Carlos Restrepo',
                'password' => Hash::make('Demo123*'),
                'organization_id' => $organization->id,
                'is_active' => true,
            ],
        );
        $owner->syncRoles(['admin_granja']);

        $vet = User::updateOrCreate(
            ['email' => 'veterinario@finca.local'],
            [
                'name' => 'Dra. Ana Gómez',
                'password' => Hash::make('Demo123*'),
                'organization_id' => $organization->id,
                'is_active' => true,
            ],
        );
        $vet->syncRoles(['veterinario']);

        $operator = User::updateOrCreate(
            ['email' => 'operario@finca.local'],
            [
                'name' => 'Pedro Jiménez',
                'password' => Hash::make('Demo123*'),
                'organization_id' => $organization->id,
                'is_active' => true,
            ],
        );
        $operator->syncRoles(['operario']);

        $plan = Plan::where('code', 'intermedio')->first();

        if ($plan) {
            Subscription::updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'billing_cycle' => 'monthly',
                    'amount' => $plan->price_monthly,
                    'current_period_start' => now()->startOfMonth()->toDateString(),
                    'current_period_end' => now()->startOfMonth()->addMonth()->subDay()->toDateString(),
                    'cancel_at_period_end' => false,
                ],
            );
        }

        $lots = collect([
            ['code' => 'NORTE', 'name' => 'Potrero Norte', 'location' => 'Zona norte', 'area_hectares' => 12.5, 'capacity' => 60],
            ['code' => 'SUR', 'name' => 'Potrero Sur', 'location' => 'Zona sur', 'area_hectares' => 8.0, 'capacity' => 40],
            ['code' => 'CORRAL1', 'name' => 'Corral 1', 'location' => 'Establo principal', 'area_hectares' => 1.2, 'capacity' => 20],
        ])->mapWithKeys(fn ($data) => [
            $data['code'] => Lot::updateOrCreate(
                ['organization_id' => $organization->id, 'code' => $data['code']],
                [...$data, 'organization_id' => $organization->id, 'is_active' => true],
            ),
        ]);

        $species = Species::whereNull('organization_id')->get()->keyBy('code');
        $breeds = Breed::whereNull('organization_id')->get();
        $eventTypes = EventType::whereNull('organization_id')->get()->keyBy('code');
        $vaccines = Vaccine::whereNull('organization_id')->get()->keyBy('name');

        $animals = [
            ['code' => 'BOV-001', 'name' => 'Lucero', 'species' => 'bovino', 'breed' => 'brahman', 'sex' => 'hembra', 'birth' => '2023-03-12', 'lot' => 'NORTE', 'start_weight' => 380],
            ['code' => 'BOV-002', 'name' => 'Tornado', 'species' => 'bovino', 'breed' => 'gyr', 'sex' => 'macho', 'birth' => '2022-08-04', 'lot' => 'NORTE', 'start_weight' => 520],
            ['code' => 'BOV-003', 'name' => 'Estrella', 'species' => 'bovino', 'breed' => 'holstein', 'sex' => 'hembra', 'birth' => '2023-11-20', 'lot' => 'SUR', 'start_weight' => 310],
            ['code' => 'BOV-004', 'name' => 'Canela', 'species' => 'bovino', 'breed' => 'cruce', 'sex' => 'hembra', 'birth' => '2024-02-15', 'lot' => 'SUR', 'start_weight' => 240],
            ['code' => 'POR-001', 'name' => 'Nube', 'species' => 'porcino', 'breed' => 'duroc', 'sex' => 'hembra', 'birth' => '2024-05-10', 'lot' => 'CORRAL1', 'start_weight' => 85],
            ['code' => 'POR-002', 'name' => 'Trueno', 'species' => 'porcino', 'breed' => 'landrace', 'sex' => 'macho', 'birth' => '2024-04-22', 'lot' => 'CORRAL1', 'start_weight' => 92],
            ['code' => 'CAP-001', 'name' => 'Blanca', 'species' => 'caprino', 'breed' => 'saanen', 'sex' => 'hembra', 'birth' => '2023-09-01', 'lot' => 'SUR', 'start_weight' => 48],
            ['code' => 'OVI-001', 'name' => 'Copo', 'species' => 'ovino', 'breed' => 'katahdin', 'sex' => 'macho', 'birth' => '2024-01-18', 'lot' => 'NORTE', 'start_weight' => 55],
            ['code' => 'EQU-001', 'name' => 'Relámpago', 'species' => 'equino', 'breed' => 'criollo', 'sex' => 'macho', 'birth' => '2021-06-30', 'lot' => 'NORTE', 'start_weight' => 340],
            ['code' => 'AVE-001', 'name' => 'Ponedora 01', 'species' => 'aves', 'breed' => 'ponedora', 'sex' => 'hembra', 'birth' => '2025-03-01', 'lot' => 'CORRAL1', 'start_weight' => 1.6],
        ];

        /** @var AnimalEventService $service */
        $service = app(AnimalEventService::class);

        foreach ($animals as $index => $data) {
            $speciesModel = $species[$data['species']] ?? null;
            $breedModel = $breeds->first(fn (Breed $breed) => $breed->species_id === $speciesModel?->id && $breed->code === $data['breed']);

            $animal = Animal::updateOrCreate(
                ['organization_id' => $organization->id, 'code' => $data['code']],
                [
                    'name' => $data['name'],
                    'tag' => 'TAG-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'species_id' => $speciesModel?->id,
                    'breed_id' => $breedModel?->id,
                    'lot_id' => $lots[$data['lot']]->id,
                    'sex' => $data['sex'],
                    'birth_date' => $data['birth'],
                    'birth_weight_kg' => round($data['start_weight'] * 0.07, 2),
                    'status' => 'activo',
                    'created_by' => $owner->id,
                    'notes' => 'Animal de demostración.',
                ],
            );

            if ($animal->events()->exists()) {
                continue;
            }

            $weight = (float) $data['start_weight'];
            $ageMonths = Carbon::parse($data['birth'])->diffInMonths(now());

            for ($month = 5; $month >= 0; $month--) {
                $date = now()->subMonths($month)->startOfMonth()->addDays(4);

                if ($date->isFuture()) {
                    $date = now()->subDays(2);
                }

                $gain = $data['species'] === 'aves' ? 0.12 : max(4, round($weight * 0.025, 2));
                $weight = round($weight + $gain, 2);

                $service->record($animal, [
                    'event_type_id' => $eventTypes['pesaje']->id,
                    'event_date' => $date->toDateString(),
                    'weight_kg' => $weight,
                    'notes' => 'Pesaje mensual de control.',
                ], $operator->id);
            }

            if ($data['species'] === 'bovino') {
                $service->record($animal, [
                    'event_type_id' => $eventTypes['vacunacion']->id,
                    'event_date' => now()->subMonths(2)->toDateString(),
                    'vaccine_id' => $vaccines['Aftosa']?->id,
                    'dose_ml' => 2,
                    'batch_number' => 'LOTE-AF-2026',
                    'next_due_date' => now()->addDays(15)->toDateString(),
                    'notes' => 'Refuerzo semestral de aftosa.',
                ], $vet->id);

                $service->record($animal, [
                    'event_type_id' => $eventTypes['desparasitacion']->id,
                    'event_date' => now()->subMonths(1)->toDateString(),
                    'treatment' => 'Ivermectina 1%',
                    'cost' => 18000,
                ], $vet->id);

                if ($data['sex'] === 'hembra' && $ageMonths > 20) {
                    $service->record($animal, [
                        'event_type_id' => $eventTypes['diagnostico_gestacion']->id,
                        'event_date' => now()->subDays(20)->toDateString(),
                        'diagnosis' => 'Gestación positiva',
                        'metadata' => ['pregnant' => true],
                    ], $vet->id);
                }
            }

            if ($data['species'] === 'porcino') {
                $service->record($animal, [
                    'event_type_id' => $eventTypes['vacunacion']->id,
                    'event_date' => now()->subMonths(1)->toDateString(),
                    'vaccine_id' => $vaccines['PRRS']?->id,
                    'dose_ml' => 2,
                    'next_due_date' => now()->addDays(25)->toDateString(),
                ], $vet->id);
            }
        }
    }
}
