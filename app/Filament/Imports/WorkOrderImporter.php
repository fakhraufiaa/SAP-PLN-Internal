<?php

namespace App\Filament\Imports;

use App\Models\WorkOrder;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ProductStatus;

class WorkOrderImporter extends Importer
{
    protected static ?string $model = WorkOrder::class;

    public static function getColumns(): array
    {
        return [
            importColumn::make('no_wo')
                ->label(__('resources.workOrder.no_wo'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('no_surat')
                ->label(__('resources.workOrder.no_surat'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('no_wbs')
                ->label(__('resources.workOrder.no_wbs'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('amp_id')
                ->label(__('resources.workOrder.amp_id'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('nama_penugasan')
                ->label(__('resources.workOrder.nama_penugasan'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('kategori')
                ->label(__('resources.workOrder.kategori'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('nilai_penugasan')
                ->label(__('resources.workOrder.nilai_penugasan'))
                ->requiredMapping()
                ->rules(['required', 'numeric']),
            ImportColumn::make('tgl_penugasan')
                ->label(__('resources.workOrder.start_date'))
                ->requiredMapping()
                ->rules(['required', 'date_format:Y-m-d']),
            ImportColumn::make('tgl_bts_penugasan')
                ->label(__('resources.workOrder.end_date'))
                ->requiredMapping()
                ->rules(['required', 'date_format:Y-m-d']),
        ];
    }

    public function resolveRecord(): ?WorkOrder
    {
        // $workOrder = new WorkOrder();
        return new WorkOrder();
    }

    public function fillRecord(): void
    {
        parent::fillRecord();

        $record = $this->getRecord();

        // Example of setting a default value
        $record->status = ProductStatus::PENDING->value;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your work order import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
