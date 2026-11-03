<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use BelongsToOrganization, HasFactory, SoftDeletes;

    protected $attributes = [
        'status' => 'activo',
    ];

    protected $fillable = [
        'organization_id',
        'code',
        'tag',
        'rfid',
        'name',
        'species_id',
        'breed_id',
        'lot_id',
        'sex',
        'birth_date',
        'birth_weight_kg',
        'dam_id',
        'sire_id',
        'status',
        'current_weight_kg',
        'last_weight_at',
        'photo_path',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'last_weight_at' => 'date',
            'birth_weight_kg' => 'decimal:2',
            'current_weight_kg' => 'decimal:2',
        ];
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(self::class, 'dam_id');
    }

    public function sire(): BelongsTo
    {
        return $this->belongsTo(self::class, 'sire_id');
    }

    public function offspring(): HasMany
    {
        return $this->hasMany(self::class, 'dam_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnimalEvent::class);
    }

    public function monthlySummaries(): HasMany
    {
        return $this->hasMany(AnimalMonthlySummary::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAgeMonthsAttribute(): ?int
    {
        return $this->birth_date?->diffInMonths(now());
    }
}
