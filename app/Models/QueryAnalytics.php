<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueryAnalytics extends Model
{
    protected $fillable = [
        'query_text',
        'sql_generated',
        'natural_response',
        'table_name',
        'user_id',
        'execution_time_ms',
        'sql_generation_time_ms',
        'natural_response_time_ms',
        'query_execution_time_ms',
        'results_count',
        'sql_success',
        'natural_response_success',
        'error_message',
        'sql_generation_model',
        'natural_response_model',
        'sql_generation_temperature',
        'sql_generation_top_p',
        'natural_response_temperature',
        'natural_response_top_p',
        'expires_at'
    ];

    protected $casts = [
        'sql_success' => 'boolean',
        'natural_response_success' => 'boolean',
        'sql_generation_temperature' => 'decimal:2',
        'sql_generation_top_p' => 'decimal:2',
        'natural_response_temperature' => 'decimal:2',
        'natural_response_top_p' => 'decimal:2',
        'expires_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('sql_success', true)
                    ->where('natural_response_success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where(function($q) {
            $q->where('sql_success', false)
              ->orWhere('natural_response_success', false);
        });
    }

    public function scopeForTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
} 