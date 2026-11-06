<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('customer_name', 160);
            $table->string('document_type', 10)->default('CC');
            $table->string('document_number', 40);
            $table->string('phone', 40)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('payment_method', 30)->default('transferencia');
            $table->string('status', 20)->default('pendiente');
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('receipt_path', 255)->nullable();
            $table->string('receipt_name', 255)->nullable();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'created_at'], 'public_orders_org_created_index');
        });

        Schema::create('public_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('public_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 200);
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index(['organization_id', 'public_order_id'], 'public_order_items_order_index');
            $table->index(['organization_id', 'product_id'], 'public_order_items_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_order_items');
        Schema::dropIfExists('public_orders');
    }
};
