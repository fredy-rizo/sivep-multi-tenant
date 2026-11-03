<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('quantity', 12, 2);
            $table->decimal('stock_before', 12, 2);
            $table->decimal('stock_after', 12, 2);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('reason', 160)->nullable();
            $table->date('movement_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'product_id', 'movement_date'], 'inventory_movements_product_date_index');
            $table->index(['organization_id', 'type'], 'inventory_movements_type_index');
            $table->index(['organization_id', 'sale_id'], 'inventory_movements_sale_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
