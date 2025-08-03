<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class AnalysisTableMetadata extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_name',
        'definition',
        'row_count',
        'expires_at'
    ];

    protected $casts = [
        'definition' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addHours(24); // todo: make it configurable
            }
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }

    public function extendExpiration(int $hours = 24): void
    {
        $this->update(['expires_at' => now()->addHours($hours)]);
    }
}
