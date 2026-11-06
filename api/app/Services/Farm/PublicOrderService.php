<?php

namespace App\Services\Farm;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Product;
use App\Models\PublicOrder;
use App\Models\PublicOrderItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PublicOrderService
{
    public const FEATURE = 'public.form';

    public const RECEIPT_DISK = 'local';

    public function __construct(protected SaleService $sales) {}

    /**
     * ¿La organización contrató el portal público?
     */
    public function hasFeature(Organization $organization): bool
    {
        return DB::table('subscriptions as s')
            ->join('plan_feature as pf', 'pf.plan_id', '=', 's.plan_id')
            ->where('s.organization_id', $organization->id)
            ->whereIn('s.status', ['active', 'trialing'])
            ->whereDate('s.current_period_end', '>=', now()->toDateString())
            ->where('pf.feature_code', self::FEATURE)
            ->exists();
    }

    /**
     * Catálogo que ve un cliente sin sesión.
     *
     * @return array<string, mixed>
     */
    public function catalog(Organization $organization): array
    {
        $products = Product::query()
            ->withoutGlobalScope('organization')
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category,
                'unit' => $product->unit,
                'description' => $product->description,
                'price' => $product->price,
                'stock' => $product->stock,
                'is_available' => (float) $product->stock > 0,
            ])
            ->values();

        return [
            'organization' => [
                'name' => $organization->name,
                'slug' => $organization->slug,
                'city' => $organization->city,
                'contact_email' => $organization->contact_email,
                'contact_phone' => $organization->contact_phone,
            ],
            'products' => $products,
            'payment_methods' => [
                ['code' => 'transferencia', 'label' => 'Transferencia bancaria'],
                ['code' => 'nequi', 'label' => 'Nequi'],
                ['code' => 'daviplata', 'label' => 'Daviplata'],
                ['code' => 'efectivo', 'label' => 'Efectivo contra entrega'],
            ],
            'max_receipt_kb' => 10240,
        ];
    }

    /**
     * Crea un pedido público con su comprobante. El stock se descuenta al
     * aprobar, no al recibir el pedido.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data, ?UploadedFile $receipt = null): PublicOrder
    {
        $items = $this->resolveItems($organization, $data['items'] ?? []);

        $receiptPath = null;
        $receiptName = null;

        if ($receipt) {
            $receiptPath = $receipt->store("public-orders/{$organization->id}", self::RECEIPT_DISK);
            $receiptName = $receipt->getClientOriginalName();
        }

        try {
            return DB::transaction(function () use ($organization, $data, $items, $receiptPath, $receiptName) {
                // Serializa la generación del consecutivo por organización.
                Organization::query()->whereKey($organization->id)->lockForUpdate()->first();

                $order = PublicOrder::query()
                    ->withoutGlobalScope('organization')
                    ->create([
                        'organization_id' => $organization->id,
                        'code' => $this->nextCode($organization),
                        'customer_name' => $data['customer_name'],
                        'document_type' => $data['document_type'] ?? 'CC',
                        'document_number' => $data['document_number'],
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'address' => $data['address'] ?? null,
                        'city' => $data['city'] ?? null,
                        'payment_method' => $data['payment_method'] ?? 'transferencia',
                        'status' => 'pendiente',
                        'total' => round(array_sum(array_column($items, 'line_total')), 2),
                        'notes' => $data['notes'] ?? null,
                        'receipt_path' => $receiptPath,
                        'receipt_name' => $receiptName,
                    ]);

                foreach ($items as $item) {
                    PublicOrderItem::query()
                        ->withoutGlobalScope('organization')
                        ->create([
                            'organization_id' => $organization->id,
                            'public_order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'line_total' => $item['line_total'],
                        ]);
                }

                return $order->load('items.product');
            });
        } catch (\Throwable $exception) {
            if ($receiptPath) {
                Storage::disk(self::RECEIPT_DISK)->delete($receiptPath);
            }

            throw $exception;
        }
    }

    /**
     * Aprueba el pedido: crea/reutiliza el cliente y registra la venta con
     * descuento de inventario.
     */
    public function approve(PublicOrder $order, User $user): PublicOrder
    {
        return DB::transaction(function () use ($order, $user) {
            $order = PublicOrder::query()
                ->withoutGlobalScope('organization')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'pendiente') {
                throw ValidationException::withMessages([
                    'order' => 'Solo se pueden aprobar pedidos pendientes.',
                ]);
            }

            $order->load('items');

            if ($order->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'order' => 'El pedido no tiene líneas para convertir en venta.',
                ]);
            }

            $customer = Customer::query()
                ->withoutGlobalScope('organization')
                ->where('organization_id', $order->organization_id)
                ->where('document_number', $order->document_number)
                ->first();

            if ($customer) {
                if (! $customer->is_active) {
                    $customer->update(['is_active' => true]);
                }
            } else {
                $customer = Customer::query()
                    ->withoutGlobalScope('organization')
                    ->create([
                        'organization_id' => $order->organization_id,
                        'document_type' => $order->document_type,
                        'document_number' => $order->document_number,
                        'name' => $order->customer_name,
                        'phone' => $order->phone,
                        'email' => $order->email,
                        'address' => $order->address,
                        'city' => $order->city,
                        'notes' => "Creado desde el pedido público {$order->code}.",
                        'is_active' => true,
                    ]);
            }

            $sale = $this->sales->create([
                'organization_id' => $order->organization_id,
                'customer_id' => $customer->id,
                'sale_date' => now()->toDateString(),
                'status' => 'pagada',
                'payment_method' => $order->payment_method,
                'notes' => "Pedido público {$order->code}.",
                'items' => $order->items->map(fn (PublicOrderItem $item) => [
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ])->all(),
            ], $user->id);

            $order->update([
                'status' => 'aprobado',
                'sale_id' => $sale->id,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);

            return $order->fresh()->load(['items.product', 'sale', 'reviewer']);
        });
    }

    public function reject(PublicOrder $order, User $user, ?string $reason = null): PublicOrder
    {
        return DB::transaction(function () use ($order, $user, $reason) {
            $order = PublicOrder::query()
                ->withoutGlobalScope('organization')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== 'pendiente') {
                throw ValidationException::withMessages([
                    'order' => 'Solo se pueden rechazar pedidos pendientes.',
                ]);
            }

            $order->update([
                'status' => 'rechazado',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $order->fresh()->load(['items.product', 'reviewer']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    protected function resolveItems(Organization $organization, array $lines): array
    {
        if ($lines === []) {
            throw ValidationException::withMessages([
                'items' => 'Agrega al menos un producto al pedido.',
            ]);
        }

        $products = Product::query()
            ->withoutGlobalScope('organization')
            ->where('organization_id', $organization->id)
            ->whereIn('id', collect($lines)->pluck('product_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $resolved = [];

        foreach ($lines as $index => $line) {
            $product = $products->get((int) ($line['product_id'] ?? 0));

            if (! $product || ! $product->is_active) {
                throw ValidationException::withMessages([
                    "items.{$index}.product_id" => 'El producto no está disponible.',
                ]);
            }

            $quantity = round((float) ($line['quantity'] ?? 0), 2);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => 'La cantidad debe ser mayor que cero.',
                ]);
            }

            if ($quantity > (float) $product->stock) {
                throw ValidationException::withMessages([
                    "items.{$index}.quantity" => "Solo hay {$product->stock} {$product->unit} de {$product->name}.",
                ]);
            }

            $unitPrice = round((float) $product->price, 2);

            $resolved[] = [
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
            ];
        }

        return $resolved;
    }

    protected function nextCode(Organization $organization): string
    {
        $last = PublicOrder::query()
            ->withoutGlobalScope('organization')
            ->where('organization_id', $organization->id)
            ->max('id') ?? 0;

        return 'PED-'.now()->format('Ymd').'-'.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
