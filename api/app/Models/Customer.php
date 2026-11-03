<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $attributes = [
        'document_type' => 'CC',
        'is_active' => true,
    ];

    protected $fillable = [
        'organization_id',
        'document_type',
        'document_number',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
