<?php

namespace Database\Seeders;

use App\Models\Breed;
use App\Models\EventType;
use App\Models\Species;
use App\Models\Vaccine;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $species = [
            'bovino' => ['Bovino', 'Bovinos'],
            'porcino' => ['Porcino', 'Porcinos'],
            'caprino' => ['Caprino', 'Caprinos'],
            'ovino' => ['Ovino', 'Ovinos'],
            'equino' => ['Equino', 'Equinos'],
            'aves' => ['Aves', 'Aves'],
        ];

        $speciesModels = [];

        foreach ($species as $code => [$name]) {
            $speciesModels[$code] = Species::updateOrCreate(
                ['organization_id' => null, 'code' => $code],
                ['name' => $name, 'is_active' => true],
            );
        }

        $breeds = [
            'bovino' => [
                'brahman' => 'Brahman',
                'holstein' => 'Holstein',
                'angus' => 'Angus',
                'gyr' => 'Gyr',
                'cruce' => 'Cruce comercial',
            ],
            'porcino' => [
                'duroc' => 'Duroc',
                'landrace' => 'Landrace',
                'large_white' => 'Large White',
            ],
            'caprino' => [
                'saanen' => 'Saanen',
                'boer' => 'Boer',
            ],
            'ovino' => [
                'katahdin' => 'Katahdin',
                'dorper' => 'Dorper',
            ],
            'equino' => [
                'criollo' => 'Criollo',
                'cuarto_milla' => 'Cuarto de milla',
            ],
            'aves' => [
                'ponedora' => 'Ponedora comercial',
                'engorde' => 'Pollo de engorde',
            ],
        ];

        foreach ($breeds as $speciesCode => $items) {
            foreach ($items as $code => $name) {
                Breed::updateOrCreate(
                    ['organization_id' => null, 'species_id' => $speciesModels[$speciesCode]->id, 'code' => $code],
                    ['name' => $name, 'is_active' => true],
                );
            }
        }

        $eventTypes = [
            ['code' => 'pesaje', 'name' => 'Pesaje', 'category' => 'peso', 'requires_weight' => true, 'sort_order' => 10],
            ['code' => 'vacunacion', 'name' => 'Vacunación', 'category' => 'sanidad', 'requires_vaccine' => true, 'sort_order' => 20],
            ['code' => 'desparasitacion', 'name' => 'Desparasitación', 'category' => 'sanidad', 'sort_order' => 30],
            ['code' => 'consulta', 'name' => 'Consulta veterinaria', 'category' => 'sanidad', 'sort_order' => 40],
            ['code' => 'diagnostico_gestacion', 'name' => 'Diagnóstico de gestación', 'category' => 'reproduccion', 'sort_order' => 50],
            ['code' => 'parto', 'name' => 'Parto', 'category' => 'reproduccion', 'sort_order' => 60],
            ['code' => 'destete', 'name' => 'Destete', 'category' => 'produccion', 'sort_order' => 70],
            ['code' => 'movimiento', 'name' => 'Movimiento de lote', 'category' => 'movimiento', 'sort_order' => 80],
            ['code' => 'venta', 'name' => 'Venta', 'category' => 'comercial', 'sort_order' => 90],
            ['code' => 'muerte', 'name' => 'Muerte', 'category' => 'comercial', 'sort_order' => 100],
        ];

        foreach ($eventTypes as $data) {
            EventType::updateOrCreate(
                ['organization_id' => null, 'code' => $data['code']],
                [...$data, 'is_active' => true],
            );
        }

        $vaccines = [
            ['name' => 'Aftosa', 'disease' => 'Fiebre aftosa', 'default_dose_ml' => 2, 'interval_days' => 180],
            ['name' => 'Rabia', 'disease' => 'Rabia', 'default_dose_ml' => 2, 'interval_days' => 365],
            ['name' => 'Clostridial', 'disease' => 'Clostridiosis', 'default_dose_ml' => 2, 'interval_days' => 365],
            ['name' => 'Brucelosis', 'disease' => 'Brucelosis', 'default_dose_ml' => 2, 'interval_days' => 365],
            ['name' => 'PRRS', 'disease' => 'Síndrome reproductivo y respiratorio porcino', 'default_dose_ml' => 2, 'interval_days' => 180],
        ];

        foreach ($vaccines as $data) {
            Vaccine::updateOrCreate(
                ['organization_id' => null, 'name' => $data['name']],
                [...$data, 'is_active' => true],
            );
        }
    }
}
