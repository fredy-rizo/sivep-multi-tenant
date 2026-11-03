<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['code' => 'livestock.core', 'name' => 'Gestión de ganado', 'category' => 'Ganadería', 'kind' => 'boolean', 'sort_order' => 10],
            ['code' => 'livestock.weights', 'name' => 'Control de pesos', 'category' => 'Ganadería', 'kind' => 'boolean', 'sort_order' => 20],
            ['code' => 'livestock.vaccines', 'name' => 'Vacunas y sanidad', 'category' => 'Ganadería', 'kind' => 'boolean', 'sort_order' => 30],
            ['code' => 'livestock.reports', 'name' => 'Reportes ganaderos', 'category' => 'Ganadería', 'kind' => 'boolean', 'sort_order' => 40],
            ['code' => 'inventory.core', 'name' => 'Inventario', 'category' => 'Operación', 'kind' => 'boolean', 'sort_order' => 50],
            ['code' => 'sales.core', 'name' => 'Ventas', 'category' => 'Comercial', 'kind' => 'boolean', 'sort_order' => 60],
            ['code' => 'clients.core', 'name' => 'Clientes', 'category' => 'Comercial', 'kind' => 'boolean', 'sort_order' => 70],
            ['code' => 'users.multi', 'name' => 'Usuarios ilimitados', 'category' => 'Plataforma', 'kind' => 'boolean', 'sort_order' => 80],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(['code' => $feature['code']], $feature);
        }

        $plans = [
            [
                'code' => 'basico',
                'name' => 'Básico',
                'description' => 'Para fincas que inician el control digital de su ganado.',
                'price_monthly' => 49000,
                'price_yearly' => 490000,
                'sort_order' => 10,
                'features' => ['livestock.core', 'livestock.weights'],
            ],
            [
                'code' => 'intermedio',
                'name' => 'Intermedio',
                'description' => 'Control sanitario, reportes y varios usuarios.',
                'price_monthly' => 89000,
                'price_yearly' => 890000,
                'sort_order' => 20,
                'features' => [
                    'livestock.core', 'livestock.weights', 'livestock.vaccines',
                    'livestock.reports', 'users.multi',
                ],
            ],
            [
                'code' => 'avanzado',
                'name' => 'Avanzado',
                'description' => 'Operación completa: ganadería, inventario, ventas y clientes.',
                'price_monthly' => 149000,
                'price_yearly' => 1490000,
                'sort_order' => 30,
                'features' => [
                    'livestock.core', 'livestock.weights', 'livestock.vaccines',
                    'livestock.reports', 'inventory.core', 'sales.core',
                    'clients.core', 'users.multi',
                ],
            ],
        ];

        foreach ($plans as $data) {
            $plan = Plan::updateOrCreate(
                ['code' => $data['code']],
                collect($data)->except('features')->all(),
            );

            DB::table('plan_feature')->where('plan_id', $plan->id)->delete();

            foreach ($data['features'] as $featureCode) {
                DB::table('plan_feature')->insert([
                    'plan_id' => $plan->id,
                    'feature_code' => $featureCode,
                    'limit_value' => null,
                ]);
            }
        }
    }
}
