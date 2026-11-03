<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Breed;
use App\Models\Customer;
use App\Models\EventType;
use App\Models\Lot;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Species;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Vaccine;
use App\Services\Farm\InventoryService;
use App\Services\Farm\SaleService;
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

        $plan = Plan::where('code', 'avanzado')->first();

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

        $this->seedFarm($organization, $owner, $operator);
    }

    private function seedFarm(Organization $organization, User $owner, User $operator): void
    {
        /** @var InventoryService $inventory */
        $inventory = app(InventoryService::class);

        $products = [
            ['sku' => 'LECHE-01', 'name' => 'Leche cruda', 'category' => 'Lácteos', 'unit' => 'litro', 'price' => 2200, 'cost' => 1400, 'stock' => 120, 'min_stock' => 30],
            ['sku' => 'QUESO-01', 'name' => 'Queso campesino', 'category' => 'Lácteos', 'unit' => 'kg', 'price' => 14000, 'cost' => 9000, 'stock' => 24, 'min_stock' => 8],
            ['sku' => 'HUEVO-01', 'name' => 'Huevo fresco', 'category' => 'Avícola', 'unit' => 'unidad', 'price' => 600, 'cost' => 380, 'stock' => 300, 'min_stock' => 80],
            ['sku' => 'CONC-01', 'name' => 'Concentrado bovino', 'category' => 'Alimento', 'unit' => 'bulto', 'price' => 78000, 'cost' => 62000, 'stock' => 18, 'min_stock' => 6],
            ['sku' => 'SAL-01', 'name' => 'Sal mineralizada', 'category' => 'Alimento', 'unit' => 'bulto', 'price' => 42000, 'cost' => 32000, 'stock' => 9, 'min_stock' => 10],
            ['sku' => 'VAC-01', 'name' => 'Vacuna aftosa', 'category' => 'Sanidad', 'unit' => 'frasco', 'price' => 85000, 'cost' => 68000, 'stock' => 7, 'min_stock' => 3],
            ['sku' => 'DESP-01', 'name' => 'Ivermectina 1%', 'category' => 'Sanidad', 'unit' => 'frasco', 'price' => 26000, 'cost' => 18000, 'stock' => 12, 'min_stock' => 4],
            ['sku' => 'ABONO-01', 'name' => 'Abono orgánico', 'category' => 'Agrícola', 'unit' => 'bulto', 'price' => 28000, 'cost' => 19000, 'stock' => 5, 'min_stock' => 6],
        ];

        $productModels = [];

        foreach ($products as $data) {
            $product = Product::firstOrCreate(
                ['organization_id' => $organization->id, 'sku' => $data['sku']],
                [
                    ...$data,
                    'stock' => 0,
                    'is_active' => true,
                    'description' => 'Producto de demostración.',
                ],
            );

            if ($product->wasRecentlyCreated && $data['stock'] > 0) {
                $inventory->register($product, [
                    'type' => 'entrada',
                    'quantity' => $data['stock'],
                    'unit_cost' => $data['cost'],
                    'reason' => 'Inventario inicial',
                    'movement_date' => now()->subDays(12)->toDateString(),
                ], $owner->id);
            }

            $productModels[$data['sku']] = $product->fresh();
        }

        $customers = [
            ['document_type' => 'CC', 'document_number' => '1045678901', 'name' => 'María Fernanda López', 'phone' => '+57 310 555 1020', 'email' => 'maria.lopez@example.com', 'city' => 'Montería', 'address' => 'Calle 24 # 8-15'],
            ['document_type' => 'NIT', 'document_number' => '900456789-2', 'name' => 'Lácteos del Sinú SAS', 'phone' => '+57 320 444 8899', 'email' => 'compras@lacteosdelsinu.co', 'city' => 'Montería', 'address' => 'Zona industrial Km 3'],
            ['document_type' => 'CC', 'document_number' => '78654321', 'name' => 'Jorge Mario Cárdenas', 'phone' => '+57 315 777 2211', 'email' => 'jorge.cardenas@example.com', 'city' => 'Cereté', 'address' => 'Carrera 5 # 12-40'],
            ['document_type' => 'NIT', 'document_number' => '901234567-8', 'name' => 'Restaurante La Cosecha', 'phone' => '+57 305 123 9944', 'email' => 'admin@lacosecha.co', 'city' => 'Montería', 'address' => 'Centro comercial Plaza Real'],
            ['document_type' => 'CC', 'document_number' => '1122334455', 'name' => 'Rosa Elena Villalba', 'phone' => '+57 312 888 4455', 'email' => 'rosa.villalba@example.com', 'city' => 'Ciénaga de Oro', 'address' => 'Transversal 7 # 4-22'],
        ];

        $customerModels = collect($customers)->mapWithKeys(fn ($data) => [
            $data['document_number'] => Customer::updateOrCreate(
                ['organization_id' => $organization->id, 'document_number' => $data['document_number']],
                [...$data, 'is_active' => true],
            ),
        ]);

        if (Sale::query()->where('organization_id', $organization->id)->exists()) {
            return;
        }

        /** @var SaleService $sales */
        $sales = app(SaleService::class);

        $saleData = [
            ['customer' => '1045678901', 'days' => 1, 'status' => 'pagada', 'payment_method' => 'nequi', 'items' => [['sku' => 'LECHE-01', 'quantity' => 20], ['sku' => 'QUESO-01', 'quantity' => 3]]],
            ['customer' => '900456789-2', 'days' => 4, 'status' => 'pagada', 'payment_method' => 'transferencia', 'discount' => 15000, 'items' => [['sku' => 'LECHE-01', 'quantity' => 60], ['sku' => 'QUESO-01', 'quantity' => 8]]],
            ['customer' => '78654321', 'days' => 8, 'status' => 'pendiente', 'payment_method' => 'credito', 'items' => [['sku' => 'HUEVO-01', 'quantity' => 120], ['sku' => 'CONC-01', 'quantity' => 2]]],
            ['customer' => '901234567-8', 'days' => 15, 'status' => 'pagada', 'payment_method' => 'efectivo', 'items' => [['sku' => 'HUEVO-01', 'quantity' => 80], ['sku' => 'QUESO-01', 'quantity' => 5]]],
            ['customer' => '1122334455', 'days' => 25, 'status' => 'pagada', 'payment_method' => 'daviplata', 'items' => [['sku' => 'SAL-01', 'quantity' => 2], ['sku' => 'DESP-01', 'quantity' => 3]]],
        ];

        foreach ($saleData as $data) {
            $items = collect($data['items'])->map(function ($item) use ($productModels) {
                $product = $productModels[$item['sku']];

                return [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => (float) $product->price,
                ];
            })->all();

            $sales->create([
                'organization_id' => $organization->id,
                'customer_id' => $customerModels[$data['customer']]->id,
                'sale_date' => now()->subDays($data['days'])->toDateString(),
                'status' => $data['status'],
                'payment_method' => $data['payment_method'],
                'discount' => $data['discount'] ?? 0,
                'items' => $items,
                'notes' => 'Venta de demostración.',
            ], $operator->id);
        }

        $soldAnimal = Animal::query()
            ->where('organization_id', $organization->id)
            ->where('code', 'BOV-002')
            ->whereNotIn('status', ['vendido', 'fallecido'])
            ->first();

        if ($soldAnimal) {
            $sales->create([
                'organization_id' => $organization->id,
                'customer_id' => $customerModels['900456789-2']->id,
                'sale_date' => now()->subDays(3)->toDateString(),
                'status' => 'pagada',
                'payment_method' => 'transferencia',
                'items' => [[
                    'animal_id' => $soldAnimal->id,
                    'quantity' => 1,
                    'unit_price' => 4200000,
                    'description' => "Animal {$soldAnimal->code} {$soldAnimal->name}",
                ]],
                'notes' => 'Venta de animal de demostración.',
            ], $owner->id);
        }
    }
}
