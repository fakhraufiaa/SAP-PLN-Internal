<?php

namespace App\Filament\Resources\ShippingDocumentResource\Pages;

use App\Filament\Resources\ShippingDocumentResource;
use Filament\Resources\Pages\Page;
use App\Models\ShippingDocument;

class ViewShippingQr extends Page
{
    protected static string $resource = ShippingDocumentResource::class;
    protected static string $view = 'filament.resources.shipping-document-resource.pages.view-shipping-qr';

    public $record;

    public function mount($record): void
    {
        $this->record = ShippingDocument::findOrFail($record);
    }
}
