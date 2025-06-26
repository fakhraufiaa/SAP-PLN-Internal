<?php

namespace App\Filament\Imports;

use App\Models\Product;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label(__('resources.product.name'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('category')
                ->label(__('resources.product.category'))
                ->requiredMapping()
                ->relationship('category', 'name')
                ->rules(['required']),
            ImportColumn::make('stock')
                ->label(__('resources.product.stock'))
                ->requiredMapping()
                ->numeric(),
            ImportColumn::make('description')
                ->label(__('resources.product.description'))
                ->requiredMapping()
                ->rules(['required'])
        ];
    }


    public function resolveRecord(): ?Model
    {
        $product = new Product();
        $product->code = 'PRD-'.str_pad((Product::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT);

        return $product;
    }

    public function fillRecord(): void
    {
        parent::fillRecord();

        $record = $this->getRecord();

        $categoryNameForBarcode = 'UNKNOWN_CATEGORY';
        $recordIdForLog = $record->id ?? 'new';

        if (isset($record->category_id) && !is_null($record->category_id)) {
            $categoryId = $record->category_id;

            $category = Category::find($categoryId);
            if ($category) {
                $categoryNameForBarcode = $category->name;
            } else {
                Log::warning("Category not found for ID: {$categoryId} during product import for record ID: " . $recordIdForLog . ". Using 'UNKNOWN_CATEGORY' for barcode generation.");
            }
        } else {
            Log::warning("Category ID missing or null in import for record ID: " . $recordIdForLog . ". Using 'UNKNOWN_CATEGORY' for barcode generation.");
        }

        $generatedBarcodeText = Product::generateNewBarcodeText($categoryNameForBarcode);
        $record->barcode = $generatedBarcodeText; // Set barcode pada $record

    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = __('resources.product.notifications.import.completed', ['count' => number_format($import->successful_rows)]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.__('resources.product.notifications.import.failed', ['count' => number_format($failedRowsCount)]);
        }

        return $body;
    }
}
