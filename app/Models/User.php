<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the tenant that owns the user.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the roles for the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('tenant_id')
            ->withTimestamps();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName, $tenantId = null): bool
    {
        $query = $this->roles()->where('name', $roleName);

        if ($tenantId !== null) {
            // Handle both Tenant objects and integer IDs
            $id = $tenantId instanceof Tenant ? $tenantId->id : $tenantId;
            $query->wherePivot('tenant_id', $id);
        }

        return $query->exists();
    }

    /**
     * Check if user is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    /**
     * Check if user is a tenant admin for a specific tenant.
     */
    public function isTenantAdmin($tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->tenant_id;

        return $this->hasRole(Role::TENANT_ADMIN, $tenantId);
    }

    /**
     * Check if user is a tenant user for a specific tenant.
     */
    public function isTenantUser($tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->tenant_id;

        return $this->hasRole(Role::TENANT_USER, $tenantId);
    }

    /**
     * Check if user belongs to a tenant.
     */
    public function belongsToTenant($tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(Role $role, $tenantId = null): void
    {
        $this->roles()->attach($role->id, [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(Role $role, $tenantId = null): void
    {
        $query = $this->roles()->where('role_id', $role->id);

        if ($tenantId) {
            $query->wherePivot('tenant_id', $tenantId);
        }

        $query->detach();
    }

    /**
     * Get all roles for a specific tenant.
     */
    public function rolesForTenant($tenantId)
    {
        return $this->roles()->wherePivot('tenant_id', $tenantId)->get();
    }
}
