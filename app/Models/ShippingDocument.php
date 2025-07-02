<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ShippingDocument extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'number',
        'invoice_id',
        'supplier_id',
        'status',
        'status_at',
        'suratJalan_document',
        'created_at'
    ];

    protected $casts = [
        'status' => ProductStatus::class,
        'status_at' => 'datetime',
        'created_at' => 'datetime',
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
            ->logOnly(['code', 'number', 'invoice.code', 'supplier.name']);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShippingDocumentProduct::class);
    }

    public function getQrCodeData(): string
    {
        // Muat relasi yang diperlukan untuk QR Code
        // Menggunakan nama relasi 'products' sesuai dengan definisi di model ini
        $this->loadMissing(['products.product', 'invoice', 'supplier']);

        $productsData = $this->products->map(function ($sdProduct) { // Menggunakan $this->products
            return [
                'product_id' => $sdProduct->product_id,
                'product_name' => $sdProduct->product->name ?? 'N/A',
                'quantity' => $sdProduct->quantity,
                'barcode' => $sdProduct->product->barcode ?? 'N/A', // Asumsi produk memiliki barcode
            ];
        })->toArray();

        $qrData = [
            'type' => 'shipping_document',
            'code' => $this->code, // Contoh: SHP-001
            'number' => $this->number,
            'invoice_number' => $this->invoice->number ?? 'N/A', // Asumsi invoice memiliki 'number'
            'supplier_name' => $this->supplier->name ?? 'N/A',
            'date' => $this -> created_at ? $this->date->format('Y-m-d') : 'N/A',
            'products' => $productsData,
        ];

        return json_encode($qrData);
    }
}
