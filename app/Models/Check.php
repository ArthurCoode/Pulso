<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Check extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'monitor_id',
        'status_code',
        'response_time_ms',
        'is_successful',
        'error',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_successful' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }
}