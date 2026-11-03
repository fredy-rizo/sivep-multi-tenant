<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aísla los modelos por organización.
 *
 * - Agrega un global scope que filtra por la organización de la petición.
 * - Rellena organization_id automáticamente al crear.
 *
 * Solo debe usarse en tablas de negocio estrictamente propias de un tenant.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope('organization', function (Builder $builder) {
            $organizationId = app(TenantContext::class)->id();

            if ($organizationId) {
                $builder->where(
                    $builder->getModel()->getTable().'.organization_id',
                    $organizationId,
                );
            }
        });

        static::creating(function (Model $model) {
            if (! $model->organization_id) {
                $organizationId = app(TenantContext::class)->id();

                if ($organizationId) {
                    $model->organization_id = $organizationId;
                }
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
