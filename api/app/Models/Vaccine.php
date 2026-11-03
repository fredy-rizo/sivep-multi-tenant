<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vaccine extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'disease',
        'manufacturer',
        'default_dose_ml',
        'interval_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_dose_ml' => 'decimal:2',
            'interval_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(AnimalEvent::class);
    }

    public function scopeAvailableTo(Builder $query, int $organizationId): Builder
    {
        return $query->where(function (Builder $query) use ($organizationId) {
            $query->whereNull('organization_id')
                ->orWhere('organization_id', $organizationId);
        });
    }
}
