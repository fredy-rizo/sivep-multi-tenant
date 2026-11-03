<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FarmApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function owner(): User
    {
        return User::where('email', 'dueno@finca.local')->firstOrFail();
    }

    public function test_inventario_clientes_y_ventas_se_listan_para_la_organizacion(): void
    {
        Sanctum::actingAs($this->owner());

        $this->getJson('/api/v1/products')->assertOk()->assertJsonCount(8, 'data');
        $this->getJson('/api/v1/customers')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson('/api/v1/sales')->assertOk()->assertJsonPath('meta.total', 6);
        $this->getJson('/api/v1/inventory/summary')->assertOk()->assertJsonPath('data.products', 8);
        $this->getJson('/api/v1/reports/farm')->assertOk()->assertJsonStructure([
            'data' => ['sales', 'inventory', 'top_products', 'sales_trend', 'low_stock', 'recent_sales'],
        ]);
    }

    public function test_crear_producto_con_stock_inicial_registra_movimiento(): void
    {
        Sanctum::actingAs($this->owner());

        $product = $this->postJson('/api/v1/products', [
            'sku' => 'TEST-001',
            'name' => 'Producto de prueba',
            'unit' => 'kg',
            'price' => 12000,
            'cost' => 8000,
            'stock' => 25,
            'min_stock' => 5,
        ])->assertCreated()
            ->assertJsonPath('data.stock', '25.00')
            ->json('data');

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product['id'],
            'type' => 'entrada',
            'quantity' => 25,
            'stock_before' => 0,
            'stock_after' => 25,
        ]);
    }

    public function test_venta_descuenta_stock_y_anulacion_lo_restaura(): void
    {
        $owner = $this->owner();
        Sanctum::actingAs($owner);

        $product = Product::where('sku', 'LECHE-01')->firstOrFail();
        $customer = Customer::firstOrFail();
        $initialStock = (float) $product->stock;

        $sale = $this->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'payment_method' => 'efectivo',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 2200,
            ]],
        ])->assertCreated()
            ->assertJsonPath('data.total', '11000.00')
            ->assertJsonPath('data.status', 'pagada')
            ->json('data');

        $this->assertSame($initialStock - 5, (float) $product->fresh()->stock);

        $movement = InventoryMovement::where('sale_id', $sale['id'])
            ->where('type', 'salida')
            ->firstOrFail();

        $this->assertSame(-5.0, (float) $movement->quantity);

        $this->postJson("/api/v1/sales/{$sale['id']}/cancel", ['reason' => 'Prueba'])
            ->assertOk()
            ->assertJsonPath('data.status', 'anulada');

        $this->assertSame($initialStock, (float) $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'sale_id' => $sale['id'],
            'type' => 'entrada',
            'quantity' => 5,
        ]);
    }

    public function test_venta_de_animal_lo_marca_vendido_y_anulacion_lo_reactiva(): void
    {
        Sanctum::actingAs($this->owner());

        $animal = Animal::where('code', 'BOV-001')->firstOrFail();

        $sale = $this->postJson('/api/v1/sales', [
            'payment_method' => 'transferencia',
            'items' => [[
                'animal_id' => $animal->id,
                'quantity' => 1,
                'unit_price' => 3500000,
            ]],
        ])->assertCreated()->json('data');

        $this->assertSame('vendido', $animal->fresh()->status);

        $this->postJson("/api/v1/sales/{$sale['id']}/cancel")
            ->assertOk();

        $this->assertSame('activo', $animal->fresh()->status);
    }

    public function test_gating_bloquea_nucleo_de_granja_en_plan_basico(): void
    {
        $owner = $this->owner();
        $basic = Plan::where('code', 'basico')->firstOrFail();

        Subscription::where('organization_id', $owner->organization_id)
            ->update(['plan_id' => $basic->id]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/products')
            ->assertStatus(402)
            ->assertJsonPath('code', 'FEATURE_NOT_INCLUDED');

        $this->getJson('/api/v1/customers')->assertStatus(402);
        $this->getJson('/api/v1/sales')->assertStatus(402);
        $this->getJson('/api/v1/animals')->assertOk();
    }

    public function test_operario_puede_vender_pero_no_anular(): void
    {
        $operator = User::where('email', 'operario@finca.local')->firstOrFail();
        $sale = Sale::firstOrFail();

        Sanctum::actingAs($operator);

        $this->getJson('/api/v1/sales')->assertOk();

        $this->postJson("/api/v1/sales/{$sale->id}/cancel")
            ->assertForbidden();
    }

    public function test_no_se_puede_ver_un_producto_de_otra_organizacion(): void
    {
        $otherOrganization = Organization::create([
            'name' => 'Otra Finca',
            'slug' => 'otra-finca-productos',
            'is_active' => true,
        ]);

        $foreign = Product::create([
            'organization_id' => $otherOrganization->id,
            'sku' => 'EXT-001',
            'name' => 'Producto externo',
            'price' => 1000,
            'stock' => 5,
        ]);

        Sanctum::actingAs($this->owner());

        $this->getJson("/api/v1/products/{$foreign->id}")->assertNotFound();
    }
}
