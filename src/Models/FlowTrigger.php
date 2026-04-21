<?php

namespace Arabiacode\LaravelFlowBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowTrigger extends Model
{
    protected $fillable = [
        'flow_id',
        'type',
        'model_class',
        'event',
        'conditions',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }
}
