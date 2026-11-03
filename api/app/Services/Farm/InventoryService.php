<?php

namespace App\Services\Farm;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Registra un movimiento y actualiza el stock de forma atómica.
     *
     * Convención de signos: las entradas suman, las salidas restan y los
     * ajustes reciben una cantidad positiva o negativa como delta.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(Product $product, array $data, ?int $userId = null): InventoryMovement
    {
        return DB::transaction(function () use ($product, $data, $userId) {
            $locked = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $type = (string) $data['type'];
            $quantity = (float) $data['quantity'];

            $signed = match ($type) {
                'entrada' => abs($quantity),
                'salida' => -abs($quantity),
                'ajuste' => $quantity,
                default => throw ValidationException::withMessages([
                    'type' => 'El tipo de movimiento no es válido.',
                ]),
            };

            if (abs($signed) < 0.0001) {
                throw ValidationException::withMessages([
                    'quantity' => 'La cantidad debe ser distinta de cero.',
                ]);
            }

            $before = round((float) $locked->stock, 2);
            $after = round($before + $signed, 2);

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuficiente para {$locked->name}. Disponible: {$before} {$locked->unit}.",
                ]);
            }

            $movement = InventoryMovement::create([
                'organization_id' => $locked->organization_id,
                'product_id' => $locked->id,
                'sale_id' => $data['sale_id'] ?? null,
                'type' => $type,
                'quantity' => $signed,
                'stock_before' => $before,
                'stock_after' => $after,
                'unit_cost' => $data['unit_cost'] ?? null,
                'reason' => $data['reason'] ?? null,
                'movement_date' => $data['movement_date'] ?? now()->toDateString(),
                'created_by' => $userId,
            ]);

            $updates = ['stock' => $after];

            if ($type === 'entrada' && array_key_exists('unit_cost', $data) && $data['unit_cost'] !== null) {
                $updates['cost'] = $data['unit_cost'];
            }

            $locked->update($updates);

            return $movement->load(['product', 'creator']);
        });
    }
}
