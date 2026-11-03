<?php

namespace App\Services\Farm;

use App\Models\Animal;
use App\Models\EventType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Livestock\AnimalEventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(
        protected InventoryService $inventory,
        protected AnimalEventService $animalEvents,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): Sale
    {
        return DB::transaction(function () use ($data, $userId) {
            $sale = Sale::create([
                'organization_id' => $data['organization_id'] ?? null,
                'code' => $this->nextCode(),
                'customer_id' => $data['customer_id'] ?? null,
                'sale_date' => $data['sale_date'] ?? now()->toDateString(),
                'status' => $data['status'] ?? 'pagada',
                'payment_method' => $data['payment_method'] ?? 'efectivo',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $subtotal = 0.0;
            $animalEventType = EventType::query()
                ->availableTo($sale->organization_id)
                ->where('code', 'venta')
                ->first();

            foreach ($data['items'] as $index => $line) {
                $quantity = round((float) ($line['quantity'] ?? 1), 2);

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => 'La cantidad debe ser mayor que cero.',
                    ]);
                }

                if (! empty($line['product_id'])) {
                    $product = Product::query()->find($line['product_id']);

                    if (! $product || ! $product->is_active) {
                        throw ValidationException::withMessages([
                            "items.{$index}.product_id" => 'El producto no está disponible.',
                        ]);
                    }

                    $unitPrice = round((float) ($line['unit_price'] ?? $product->price), 2);
                    $lineTotal = round($quantity * $unitPrice, 2);

                    SaleItem::create([
                        'organization_id' => $sale->organization_id,
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'description' => $line['description'] ?? $product->name,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);

                    $this->inventory->register($product, [
                        'type' => 'salida',
                        'quantity' => $quantity,
                        'reason' => "Venta {$sale->code}",
                        'movement_date' => $sale->sale_date->toDateString(),
                        'sale_id' => $sale->id,
                    ], $userId);

                    $subtotal += $lineTotal;

                    continue;
                }

                if (! empty($line['animal_id'])) {
                    $animal = Animal::query()->find($line['animal_id']);

                    if (! $animal) {
                        throw ValidationException::withMessages([
                            "items.{$index}.animal_id" => 'El animal no existe en tu organización.',
                        ]);
                    }

                    if (in_array($animal->status, ['vendido', 'fallecido'], true)) {
                        throw ValidationException::withMessages([
                            "items.{$index}.animal_id" => "El animal {$animal->code} no está disponible para la venta.",
                        ]);
                    }

                    $unitPrice = round((float) ($line['unit_price'] ?? 0), 2);
                    $lineTotal = round($quantity * $unitPrice, 2);

                    SaleItem::create([
                        'organization_id' => $sale->organization_id,
                        'sale_id' => $sale->id,
                        'animal_id' => $animal->id,
                        'description' => $line['description']
                            ?? trim("Animal {$animal->code} {$animal->name}"),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ]);

                    if ($animalEventType) {
                        $this->animalEvents->record($animal, [
                            'event_type_id' => $animalEventType->id,
                            'event_date' => $sale->sale_date->toDateString(),
                            'notes' => "Venta {$sale->code}",
                        ], $userId);
                    } else {
                        $animal->forceFill(['status' => 'vendido'])->save();
                    }

                    $subtotal += $lineTotal;

                    continue;
                }

                throw ValidationException::withMessages([
                    "items.{$index}" => 'Cada línea debe incluir un producto o un animal.',
                ]);
            }

            $discount = min($subtotal, max(0, round((float) ($data['discount'] ?? 0), 2)));
            $tax = max(0, round((float) ($data['tax'] ?? 0), 2));

            $sale->update([
                'subtotal' => round($subtotal, 2),
                'discount' => $discount,
                'tax' => $tax,
                'total' => round($subtotal - $discount + $tax, 2),
            ]);

            return $sale->load(['customer', 'items.product', 'items.animal', 'creator']);
        });
    }

    public function cancel(Sale $sale, ?string $reason = null, ?int $userId = null): Sale
    {
        return DB::transaction(function () use ($sale, $reason, $userId) {
            $sale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->status === 'anulada') {
                throw ValidationException::withMessages([
                    'sale' => 'La venta ya está anulada.',
                ]);
            }

            $sale->loadMissing('items');

            foreach ($sale->items as $item) {
                if ($item->product_id) {
                    $product = Product::query()->find($item->product_id);

                    if ($product) {
                        $this->inventory->register($product, [
                            'type' => 'entrada',
                            'quantity' => abs((float) $item->quantity),
                            'reason' => "Anulación venta {$sale->code}",
                            'movement_date' => now()->toDateString(),
                            'sale_id' => $sale->id,
                        ], $userId);
                    }
                }

                if ($item->animal_id) {
                    Animal::query()
                        ->whereKey($item->animal_id)
                        ->update(['status' => 'activo']);
                }
            }

            $sale->update([
                'status' => 'anulada',
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            return $sale->load(['customer', 'items.product', 'items.animal', 'creator']);
        });
    }

    protected function nextCode(): string
    {
        $next = (int) Sale::query()->max('id') + 1;

        return 'VTA-'.now()->format('Ymd').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
