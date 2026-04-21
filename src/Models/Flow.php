<?php

namespace Arabiacode\LaravelFlowBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Flow extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function triggers(): HasMany
    {
        return $this->hasMany(FlowTrigger::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(FlowNode::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(FlowConnection::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(FlowExecution::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(FlowVariable::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
