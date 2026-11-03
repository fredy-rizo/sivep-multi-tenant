<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $attributes = [
        'unit' => 'unidad',
        'price' => 0,
        'stock' => 0,
        'min_stock' => 0,
        'is_active' => true,
    ];

    protected $fillable = [
        'organization_id',
        'sku',
        'name',
        'category',
        'unit',
        'description',
        'price',
        'cost',
        'stock',
        'min_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'stock' => 'decimal:2',
            'min_stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->stock <= (float) $this->min_stock;
    }
}
