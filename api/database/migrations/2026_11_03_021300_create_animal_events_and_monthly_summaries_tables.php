<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_type_id')->constrained()->restrictOnDelete();
            $table->date('event_date');
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->foreignId('vaccine_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('dose_ml', 8, 2)->nullable();
            $table->string('batch_number', 80)->nullable();
            $table->date('next_due_date')->nullable();
            $table->string('diagnosis')->nullable();
            $table->text('treatment')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'animal_id', 'event_date']);
            $table->index(['organization_id', 'event_type_id']);
            $table->index(['organization_id', 'next_due_date']);
            $table->index(['organization_id', 'vaccine_id']);
        });

        Schema::create('animal_monthly_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->char('period', 7);
            $table->decimal('last_weight_kg', 8, 2)->nullable();
            $table->decimal('avg_weight_kg', 8, 2)->nullable();
            $table->decimal('weight_gain_kg', 8, 2)->nullable();
            $table->decimal('daily_gain_g', 8, 2)->nullable();
            $table->unsignedInteger('events_count')->default(0);
            $table->unsignedInteger('vaccines_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['animal_id', 'period']);
            $table->index(['organization_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_monthly_summaries');
        Schema::dropIfExists('animal_events');
    }
};
