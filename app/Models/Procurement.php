<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Procurement extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'number',
        'amp_id',
        'penugasan_id',
        'kategori',
        'nilai_penugasan',
        'start_date',
        'end_date',
        'status',
        'status_at',
        'dkmj_document',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => ProductStatus::class,
        'status_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if ($model->status && !$model->status_at) {
                $model->status_at = now();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('status')) {
                $model->status_at = now();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'number', 'contract.name', 'start_date', 'end_date'])
            ->logOnlyDirty();
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProcurementProduct::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

     public function workOrder(): BelongsTo
    {

        return $this->belongsTo(WorkOrder::class, 'number', 'id');
    }

     public function getWorkOrderNoAttribute(): ?string
    {
        return $this->workOrder->no_wo ?? null;
    }
}



