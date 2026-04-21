<?php

namespace Arabiacode\LaravelFlowBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlowNode extends Model
{
    protected $fillable = [
        'flow_id',
        'type',
        'name',
        'data',
        'sort_order',
        'position_x',
        'position_y',
    ];

    protected $casts = [
        'data' => 'array',
        'position_x' => 'integer',
        'position_y' => 'integer',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }

    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(FlowConnection::class, 'from_node_id');
    }

    public function incomingConnections(): HasMany
    {
        return $this->hasMany(FlowConnection::class, 'to_node_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FlowLog::class, 'node_id');
    }
}
