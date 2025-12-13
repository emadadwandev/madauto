<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'properties',
        'causer_id',
        'causer_type',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function causer(): BelongsTo
    {
        return $this->morphTo('causer');
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeLastDay($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    public function scopeLastWeek($query)
    {
        return $query->where('created_at', '>=', now()->subWeek());
    }

    public function getLastActivityAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getIconAttribute(): string
    {
        return match ($this->action) {
            'user.invited' => 'heroicon-o-envelope',
            'user.accepted_invitation' => 'heroicon-o-check-circle',
            'user.role_changed' => 'heroicon-o-shield-check',
            'user.removed_from_tenant' => 'heroicon-o-trash',
            'user.login' => 'heroicon-o-key',
            'user.logout' => 'heroicon-o-arrow-right-on-rectangle',
            'invitation.resent' => 'heroicon-o-arrow-path',
            'order.processed' => 'heroicono-shopping-cart',
            'menu.created' => 'heroicono-plus-circle',
            'menu.updated' => 'heroicono-pencil-square',
            'menu.published' => 'heroicono-paper-airplane',
            'location.created' => 'heroicono-map-pin',
            'location.updated' => 'heroicono-pencil',
            'default' => 'heroicono-information-circle',
        };
    }

    public function getColorAttribute(): string
    {
        return match ($this->action) {
            'user.invited', 'invitation.resent' => 'blue',
            'user.accepted_invitation', 'user.login' => 'green',
            'user.role_changed' => 'purple',
            'user.removed_from_tenant', 'user.logout' => 'gray',
            'order.processed' => 'indigo',
            'menu.created', 'location.created' => 'green',
            'menu.updated', 'location.updated' => 'yellow',
            'menu.published' => 'blue',
            'default' => 'gray',
        };
    }
}
