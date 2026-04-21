<?php

namespace Arabiacode\LaravelFlowBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'execution_id',
        'node_id',
        'status',
        'message',
        'data',
        'created_at',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function execution(): BelongsTo
    {
        return $this->belongsTo(FlowExecution::class, 'execution_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(FlowNode::class, 'node_id');
    }
}
