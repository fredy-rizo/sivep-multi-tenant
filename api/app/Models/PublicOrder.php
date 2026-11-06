<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicOrder extends Model
{
    use BelongsToOrganization, HasFactory;

    protected $attributes = [
        'status' => 'pendiente',
        'payment_method' => 'transferencia',
        'document_type' => 'CC',
        'total' => 0,
    ];

    protected $fillable = [
        'organization_id',
        'code',
        'customer_name',
        'document_type',
        'document_number',
        'phone',
        'email',
        'address',
        'city',
        'payment_method',
        'status',
        'total',
        'notes',
        'receipt_path',
        'receipt_name',
        'sale_id',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PublicOrderItem::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getHasReceiptAttribute(): bool
    {
        return filled($this->receipt_path);
    }
}
