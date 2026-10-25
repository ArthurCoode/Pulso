<?php

namespace App\Models;

use App\Enums\MonitorStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Monitor extends Model
{
    protected $fillable = [
        'name',
        'url',
        'method',
        'expected_status',
        'timeout',
        'interval_minutes',
        'keyword',
        'is_active',
        'status',
        'consecutive_failures',
        'last_checked_at',
        'next_check_at',
    ];

    /**
     * Valores padrão do objeto em memória (espelham os defaults da migration).
     */
    protected $attributes = [
        'method' => 'GET',
        'expected_status' => 200,
        'timeout' => 10,
        'interval_minutes' => 1,
        'is_active' => true,
        'status' => 'unknown',
        'consecutive_failures' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => MonitorStatus::class,
            'is_active' => 'boolean',
            'last_checked_at' => 'datetime',
            'next_check_at' => 'datetime',
        ];
    }

    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function openIncident(): HasOne
    {
        return $this->hasOne(Incident::class)->whereNull('resolved_at');
    }

    /**
     * A checagem mais recente (o id cresce com o tempo, então o maior é o último).
     */
    public function latestCheck(): HasOne
    {
        return $this->hasOne(Check::class)->latestOfMany('id');
    }

    /**
     * Monitores ativos cuja próxima checagem já venceu (ou nunca rodou).
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('next_check_at')
                  ->orWhere('next_check_at', '<=', now());
            });
    }
}