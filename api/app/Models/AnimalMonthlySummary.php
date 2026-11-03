<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalMonthlySummary extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'animal_id',
        'period',
        'last_weight_kg',
        'avg_weight_kg',
        'weight_gain_kg',
        'daily_gain_g',
        'events_count',
        'vaccines_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_weight_kg' => 'decimal:2',
            'avg_weight_kg' => 'decimal:2',
            'weight_gain_kg' => 'decimal:2',
            'daily_gain_g' => 'decimal:2',
            'events_count' => 'integer',
            'vaccines_count' => 'integer',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
