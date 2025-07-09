<?php
namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'no_wo',
        'no_surat',
        'no_wbs',
        'amp_id',
        'nama_penugasan',
        'kategori',
        'nilai_penugasan',
        'tgl_penugasan',
        'tgl_bts_penugasan',
        'status',
        'status_at',
    ];

    protected $dates = [
        'tgl_penugasan',
        'tgl_bts_penugasan',
        'status_at',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\WorkOrderItem::class, 'work_order_id');
    }

    public function procurements(): HasMany
    {
        return $this->hasMany(Procurement::class, 'number', 'id');
    }
}
