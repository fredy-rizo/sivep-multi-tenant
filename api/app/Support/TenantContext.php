<?php

namespace App\Support;

use App\Models\Organization;

/**
 * Organización resuelta para la petición actual.
 *
 * Se registra como singleton en AppServiceProvider. El middleware
 * ResolveOrganization la establece después de autenticar y los modelos que
 * usan el trait BelongsToOrganization la leen para filtrar y rellenar
 * organization_id.
 */
class TenantContext
{
    protected ?Organization $organization = null;

    public function set(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function organization(): ?Organization
    {
        return $this->organization;
    }

    public function id(): ?int
    {
        return $this->organization?->id;
    }

    public function has(): bool
    {
        return $this->organization !== null;
    }

    public function clear(): void
    {
        $this->organization = null;
    }
}
