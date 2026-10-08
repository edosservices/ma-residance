<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Tenant;

class CurrentContext
{
    private ?int $organizationId = null;

    private ?Organization $organization = null;

    private ?OrganizationMember $member = null;

    private ?Tenant $tenant = null;

    public function clear(): void
    {
        $this->organizationId = null;
        $this->organization = null;
        $this->member = null;
        $this->tenant = null;
    }

    public function setOrganization(Organization $organization): void
    {
        $this->organization = $organization;
        $this->organizationId = $organization->id;
    }

    public function setMember(OrganizationMember $member): void
    {
        $this->member = $member;
    }

    public function setTenant(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function organizationId(): ?int
    {
        return $this->organizationId;
    }

    public function organization(): Organization
    {
        if ($this->organization === null) {
            throw new DomainException('Aucune organisation active.');
        }

        return $this->organization;
    }

    public function member(): ?OrganizationMember
    {
        return $this->member;
    }

    public function optionalTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function tenant(): Tenant
    {
        if ($this->tenant === null) {
            throw new DomainException('Aucun dossier locataire actif.');
        }

        return $this->tenant;
    }
}
