<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Species extends Model
{
    use HasFactory;

    protected $fillable = ['organization_id', 'code', 'name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function breeds(): HasMany
    {
        return $this->hasMany(Breed::class);
    }

    public function animals(): HasMany
    {
        return $this->hasMany(Animal::class);
    }

    /**
     * Catálogo disponible para una organización: global (NULL) o propio.
     */
    public function scopeAvailableTo(Builder $query, int $organizationId): Builder
    {
        return $query->where(function (Builder $query) use ($organizationId) {
            $query->whereNull('organization_id')
                ->orWhere('organization_id', $organizationId);
        });
    }
}
