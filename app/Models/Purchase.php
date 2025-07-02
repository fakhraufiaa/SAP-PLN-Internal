<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'number',
        'supplier_id',
        'purchase_date',
        'procurement_id',
        'status',
        'status_at',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
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
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['supplier.name', 'purchase_date', 'procurement.name'])
            ->logOnlyDirty();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(Procurement::class, 'number');
    }

    public function products(): HasMany
    {
        return $this->hasMany(PurchaseProduct::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

}
