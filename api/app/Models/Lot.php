<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'location',
        'area_hectares',
        'capacity',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'area_hectares' => 'decimal:2',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }
}
