<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    // Role constants
    public const SUPER_ADMIN = 'super_admin';

    public const TENANT_ADMIN = 'tenant_admin';

    public const TENANT_USER = 'tenant_user';

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Get all users with this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    /**
     * Check if this is the super admin role.
     */
    public function isSuperAdmin(): bool
    {
        return $this->name === self::SUPER_ADMIN;
    }

    /**
     * Check if this is a tenant admin role.
     */
    public function isTenantAdmin(): bool
    {
        return $this->name === self::TENANT_ADMIN;
    }

    /**
     * Check if this is a tenant user role.
     */
    public function isTenantUser(): bool
    {
        return $this->name === self::TENANT_USER;
    }

    /**
     * Check if this role requires a tenant context.
     */
    public function requiresTenant(): bool
    {
        return ! $this->isSuperAdmin();
    }

    /**
     * Get all tenant-specific roles (excludes super admin).
     */
    public function scopeTenantRoles($query)
    {
        return $query->whereIn('name', [self::TENANT_ADMIN, self::TENANT_USER]);
    }

    /**
     * Scope for finding by name.
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }
}
