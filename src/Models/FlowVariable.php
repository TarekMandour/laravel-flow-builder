<?php

namespace Arabiacode\LaravelFlowBuilder\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowVariable extends Model
{
    protected $fillable = [
        'flow_id',
        'key',
        'value',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(Flow::class);
    }
}
