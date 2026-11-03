<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalEvent extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'animal_id',
        'event_type_id',
        'event_date',
        'weight_kg',
        'vaccine_id',
        'dose_ml',
        'batch_number',
        'next_due_date',
        'diagnosis',
        'treatment',
        'cost',
        'notes',
        'metadata',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'next_due_date' => 'date',
            'weight_kg' => 'decimal:2',
            'dose_ml' => 'decimal:2',
            'cost' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
