<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShippingDocumentProduct extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'shipping_document_id',
        'product_id',
        'quantity',
        'created_at',
        'status',
        'status_at',
    ];

    protected $casts = [
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
            ->logOnly(['shipping_document.code', 'product.name', 'price', 'quantity']);
    }

    public function shippingDocument(): BelongsTo
    {
        return $this->belongsTo(ShippingDocument::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}
