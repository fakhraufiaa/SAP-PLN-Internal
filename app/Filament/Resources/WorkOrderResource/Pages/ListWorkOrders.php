<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ImportAction;
use App\Filament\Imports\WorkOrderImporter;

class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()->importer(WorkOrderImporter::class)->csvDelimiter(';'),
            Actions\CreateAction::make(),
        ];
    }
}
