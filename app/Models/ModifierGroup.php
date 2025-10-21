<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ModifierGroup extends Model
{
    use HasTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'selection_type',
        'min_selections',
        'max_selections',
        'is_required',
        'is_active',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include active modifier groups.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include required groups.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Get the modifiers in this group.
     */
    public function modifiers(): BelongsToMany
    {
        return $this->belongsToMany(Modifier::class, 'modifier_group_modifier')
            ->withPivot('sort_order', 'is_default')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get only active modifiers in this group.
     */
    public function activeModifiers(): BelongsToMany
    {
        return $this->modifiers()->where('modifiers.is_active', true);
    }

    /**
     * Get the menu items that use this modifier group.
     */
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'menu_item_modifier_group')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Check if group allows multiple selections.
     */
    public function allowsMultiple(): bool
    {
        return $this->selection_type === 'multiple';
    }

    /**
     * Check if group requires selection.
     */
    public function requiresSelection(): bool
    {
        return $this->is_required || $this->min_selections > 0;
    }

    /**
     * Get validation rules based on group settings.
     */
    public function getValidationRules(): array
    {
        $rules = [];

        if ($this->is_required) {
            $rules[] = 'required';
        }

        if ($this->selection_type === 'single') {
            $rules[] = 'max:1';
        }

        if ($this->max_selections) {
            $rules[] = 'max:'.$this->max_selections;
        }

        if ($this->min_selections > 0) {
            $rules[] = 'min:'.$this->min_selections;
        }

        return $rules;
    }
}
