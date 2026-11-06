<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\Product;
use App\Models\PublicOrder;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PublicOrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function owner(): User
    {
        return User::where('email', 'dueno@finca.local')->firstOrFail();
    }

    private function product(string $sku = 'LECHE-01'): Product
    {
        return Product::where('sku', $sku)->firstOrFail();
    }

    /**
     * @param  array<int, array{product_id: int, quantity: float|int}>  $items
     * @return array<string, mixed>
     */
    private function payload(array $items, string $document = '1099887766'): array
    {
        return [
            'customer_name' => 'Cliente del portal',
            'document_type' => 'CC',
            'document_number' => $document,
            'phone' => '+57 300 000 0000',
            'email' => 'cliente@example.com',
            'city' => 'Montería',
            'payment_method' => 'transferencia',
            'notes' => 'Pedido de prueba',
            'receipt' => UploadedFile::fake()->image('comprobante.png'),
            'items' => $items,
        ];
    }

    public function test_catalogo_publico_muestra_productos_de_la_organizacion(): void
    {
        $this->getJson('/api/v1/public/finca-la-esperanza/catalog')
            ->assertOk()
            ->assertJsonPath('data.organization.name', 'Finca La Esperanza')
            ->assertJsonCount(8, 'data.products')
            ->assertJsonStructure([
                'data' => [
                    'organization' => ['name', 'slug'],
                    'products' => [['id', 'name', 'price', 'stock', 'is_available']],
                    'payment_methods',
                    'max_receipt_kb',
                ],
            ]);
    }

    public function test_crear_pedido_publico_guarda_comprobante_y_calcula_total(): void
    {
        Storage::fake('local');

        $product = $this->product();

        $response = $this->post('/api/v1/public/finca-la-esperanza/orders', $this->payload([
            ['product_id' => $product->id, 'quantity' => 4],
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pendiente')
            ->assertJsonPath('data.customer_name', 'Cliente del portal')
            ->assertJsonPath('data.items_count', 1);

        $order = PublicOrder::query()
            ->withoutGlobalScope('organization')
            ->where('code', $response->json('data.code'))
            ->firstOrFail();

        $this->assertSame(round((float) $product->price * 4, 2), (float) $order->total);
        $this->assertNotNull($order->receipt_path);
        Storage::disk('local')->assertExists($order->receipt_path);
    }

    public function test_plan_sin_portal_publico_responde_402(): void
    {
        $organization = $this->owner()->organization;
        $basic = Plan::where('code', 'basico')->firstOrFail();

        Subscription::where('organization_id', $organization->id)
            ->update(['plan_id' => $basic->id]);

        $this->getJson('/api/v1/public/finca-la-esperanza/catalog')
            ->assertStatus(402)
            ->assertJsonPath('code', 'FEATURE_NOT_INCLUDED');

        $product = $this->product();

        $this->post('/api/v1/public/finca-la-esperanza/orders', $this->payload([
            ['product_id' => $product->id, 'quantity' => 1],
        ]), ['Accept' => 'application/json'])
            ->assertStatus(402);
    }

    public function test_aprobar_pedido_crea_cliente_y_venta_y_descuenta_stock(): void
    {
        Storage::fake('local');
        $owner = $this->owner();
        Sanctum::actingAs($owner);

        $product = $this->product();
        $initialStock = (float) $product->stock;

        $code = $this->post('/api/v1/public/finca-la-esperanza/orders', $this->payload([
            ['product_id' => $product->id, 'quantity' => 3],
        ]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->json('data.code');

        $order = PublicOrder::query()
            ->withoutGlobalScope('organization')
            ->where('code', $code)
            ->firstOrFail();

        $this->postJson("/api/v1/public-orders/{$order->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'aprobado')
            ->assertJsonPath('data.sale.status', 'pagada');

        $this->assertSame($initialStock - 3, (float) $product->fresh()->stock);
        $this->assertNotNull($order->fresh()->sale_id);
        $this->assertSame($owner->id, $order->fresh()->reviewed_by);

        $this->assertDatabaseHas('customers', [
            'organization_id' => $owner->organization_id,
            'document_number' => '1099887766',
            'name' => 'Cliente del portal',
        ]);

        $this->assertDatabaseHas('sales', [
            'id' => $order->fresh()->sale_id,
            'organization_id' => $owner->organization_id,
            'total' => round((float) $product->price * 3, 2),
        ]);

        // Un pedido ya revisado no se puede aprobar dos veces.
        $this->postJson("/api/v1/public-orders/{$order->id}/approve")
            ->assertStatus(422);
    }

    public function test_rechazar_pedido_guarda_motivo_y_no_crea_venta(): void
    {
        $owner = $this->owner();
        Sanctum::actingAs($owner);

        $order = PublicOrder::query()->where('status', 'pendiente')->firstOrFail();
        $salesBefore = Sale::query()->count();

        $this->postJson("/api/v1/public-orders/{$order->id}/reject", [
            'reason' => 'El comprobante no coincide con el valor.',
        ])->assertOk()
            ->assertJsonPath('data.status', 'rechazado')
            ->assertJsonPath('data.rejection_reason', 'El comprobante no coincide con el valor.');

        $this->assertSame($salesBefore, Sale::query()->count());
        $this->assertNull($order->fresh()->sale_id);
    }

    public function test_bandeja_lista_y_resume_pedidos_de_la_organizacion(): void
    {
        $owner = $this->owner();
        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/public-orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'pendiente')
            ->assertJsonPath('data.0.has_receipt', true);

        $this->getJson('/api/v1/public-orders/summary')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.pending', 1);
    }

    public function test_no_se_puede_revisar_un_pedido_de_otra_organizacion(): void
    {
        $other = Organization::create([
            'name' => 'Finca Ajena',
            'slug' => 'finca-ajena-pedidos',
            'is_active' => true,
        ]);

        $product = Product::query()
            ->withoutGlobalScope('organization')
            ->where('organization_id', $this->owner()->organization_id)
            ->firstOrFail();

        $order = PublicOrder::query()
            ->withoutGlobalScope('organization')
            ->create([
                'organization_id' => $other->id,
                'code' => 'PED-AJENO-0001',
                'customer_name' => 'Cliente ajeno',
                'document_number' => '999999999',
                'status' => 'pendiente',
                'total' => 1000,
            ]);

        $order->items()->create([
            'organization_id' => $other->id,
            'product_id' => $product->id,
            'description' => 'Producto',
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
        ]);

        Sanctum::actingAs($this->owner());

        $this->getJson("/api/v1/public-orders/{$order->id}")->assertNotFound();
        $this->postJson("/api/v1/public-orders/{$order->id}/approve")->assertNotFound();
        $this->postJson("/api/v1/public-orders/{$order->id}/reject", ['reason' => 'No'])->assertNotFound();
    }

    public function test_operario_no_puede_ver_ni_aprobar_pedidos(): void
    {
        $operator = User::where('email', 'operario@finca.local')->firstOrFail();
        $order = PublicOrder::query()->where('status', 'pendiente')->firstOrFail();

        Sanctum::actingAs($operator);

        $this->getJson('/api/v1/public-orders')->assertForbidden();
        $this->postJson("/api/v1/public-orders/{$order->id}/approve")->assertForbidden();
    }

    public function test_veterinario_puede_ver_pero_no_aprobar_pedidos(): void
    {
        $vet = User::where('email', 'veterinario@finca.local')->firstOrFail();
        $order = PublicOrder::query()->where('status', 'pendiente')->firstOrFail();

        Sanctum::actingAs($vet);

        $this->getJson('/api/v1/public-orders')->assertOk();
        $this->postJson("/api/v1/public-orders/{$order->id}/approve")->assertForbidden();
    }

    public function test_rechaza_cantidad_superior_al_stock_disponible(): void
    {
        Storage::fake('local');
        $product = $this->product();

        $this->post('/api/v1/public/finca-la-esperanza/orders', $this->payload([
            ['product_id' => $product->id, 'quantity' => (float) $product->stock + 100],
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.quantity');
    }
}
