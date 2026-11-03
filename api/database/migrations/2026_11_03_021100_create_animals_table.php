<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('tag', 60)->nullable();
            $table->string('rfid', 80)->nullable();
            $table->string('name', 120)->nullable();
            $table->foreignId('species_id')->constrained()->restrictOnDelete();
            $table->foreignId('breed_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sex', 10);
            $table->date('birth_date')->nullable();
            $table->decimal('birth_weight_kg', 8, 2)->nullable();
            $table->foreignId('dam_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->foreignId('sire_id')->nullable()->constrained('animals')->nullOnDelete();
            $table->string('status', 20)->default('activo');
            $table->decimal('current_weight_kg', 8, 2)->nullable();
            $table->date('last_weight_at')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->unique(['organization_id', 'rfid']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'lot_id']);
            $table->index(['organization_id', 'species_id']);
            $table->index(['organization_id', 'birth_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
