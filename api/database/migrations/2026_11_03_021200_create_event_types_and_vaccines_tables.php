<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name', 120);
            $table->string('category', 30);
            $table->boolean('requires_weight')->default(false);
            $table->boolean('requires_vaccine')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('vaccines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('disease', 150)->nullable();
            $table->string('manufacturer', 150)->nullable();
            $table->decimal('default_dose_ml', 8, 2)->nullable();
            $table->unsignedInteger('interval_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccines');
        Schema::dropIfExists('event_types');
    }
};
