<?php

namespace App\Filament\Resources;

use App\Enums\ProductStatus;
use App\Filament\Resources\ShippingDocumentResource\Pages;
use App\Filament\Resources\ShippingDocumentResource\RelationManagers\ProductsRelationManager;
use App\Models\ShippingDocument;
use App\Models\ShippingDocumentProduct;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString; // Diperlukan untuk HtmlString
use Filament\Forms\Get; // Diperlukan untuk Get
use Filament\Notifications\Notification; // Diperlukan untuk Notifikasi


class ShippingDocumentResource extends Resource
{
    protected static ?string $model = ShippingDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document';

    protected static ?string $navigationGroup = 'Procurement';

    protected static ?int $navigationSort = 50;

    public static function getModelLabel(): string
    {
        return __('resources.shipping_document.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.shipping_document.label');
    }

    public static function getBreadcrumb(): string
    {
        return __('resources.shipping_document.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resources.shipping_document.label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resources.procurement.documents'))
                    ->schema([
                        Forms\Components\FileUpload::make('suratJalan_document')
                            ->label(__('Input File Surat Jalan'))
                            ->helperText('Upload dokumen Surat Jalan dalam format PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('public') // Explicitly set the disk to public
                            ->directory('procurement-suratJalan-documents')
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->openable()
                            ->previewable(true)
                            // Custom file naming based on procurement code
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file, callable $get) {
                                    $code = $get('code');
                                    return "Surat-Jalan_{$code}.pdf";
                                }
                            )
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\TextInput::make('code')
                    ->label(__('resources.shipping_document.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'SHP-'.str_pad((ShippingDocument::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT))
                    ->readOnly(),

                // Updated Select for procurement numbers
                Forms\Components\Select::make('invoice_id')
                    ->label(__('resources.shipping_document.invoice'))
                    ->options(function () {
                        return \App\Models\Invoice::pluck('code', 'id');
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                        if (!$state) {
                            $set('number', null);
                            $set('penugasan_id_display', null);
                            $set('supplier_id', null);
                            return;
                        }

                        $invoice = \App\Models\Invoice::with('purchase.procurement')->find($state);
                        if ($invoice && $invoice->purchase && $invoice->purchase->procurement) {
                            $set('number', $invoice->purchase->procurement->number); // <-- simpan penugasan_id ke kolom number
                            $set('penugasan_id_display', $invoice->purchase->procurement->penugasan_id); // tampilkan penugasan_id
                        } else {
                            $set('number', null);
                            $set('penugasan_id_display', null);
                        }
                        $set('supplier_id', $invoice?->supplier_id);
                    })
                    ->afterStateHydrated(function ($state, $record, \Filament\Forms\Set $set) {
                        if ($record && $record->invoice && $record->invoice->purchase && $record->invoice->purchase->procurement) {
                            $set('number', $record->invoice->purchase->procurement->number);
                            $set('penugasan_id_display', $record->invoice->purchase->procurement->penugasan_id);
                        }
                    })
                    ->required(),

                Forms\Components\Hidden::make('number')
                    ->required(),

                Forms\Components\TextInput::make('penugasan_id_display')
                    ->label('Penugasan')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('supplier_id')
                    ->label(__('resources.shipping_document.supplier'))
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('status')
                    ->label(__('resources.shipping_document.status'))
                    ->options(ProductStatus::class)
                    ->enum(ProductStatus::class)
                    ->default(ProductStatus::PENDING)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('resources.shipping_document.code'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('resources.shipping_document.status'))
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::CANCELED => 'danger',
                        ProductStatus::PENDING => 'warning',
                        ProductStatus::DONE => 'success',
                    })
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->getLabel())
                    ->sortable(),

                Tables\Columns\TextColumn::make('procurement.code')
                    ->label(__('resources.shipping_document.number'))
                    ->formatStateUsing(function ($record) {
                        // Get the procurement number through the invoice->purchase relationship
                        return $record->invoice?->purchase?->procurement?->code ?? 'N/A';
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice.code')
                    ->label(__('resources.shipping_document.invoice'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('resources.shipping_document.supplier'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status_at')
                    ->label(__('resources.shipping_document.status_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resources.shipping_document.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resources.shipping_document.updated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('resources.shipping_document.deleted_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('suratJalan_document')
                    ->label('File Surat Jalan')
                    ->formatStateUsing(function ($state, $record) {
                        if (empty($state)) {
                            return '-';
                        }

                        return "Surat-Jalan_{$record->code}.pdf";
                    })
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(fn ($record) => $record->suratJalan_document ? Storage::disk('public')->url($record->suratJalan_document) : null)
                    ->openUrlInNewTab()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('invoice')
                    ->label(__('resources.invoice.invoice'))
                    ->relationship('invoice', 'code')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('supplier')
                    ->label(__('resources.shipping_document.supplier'))
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\Action::make('generate_qr')
                    ->label('Lihat QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('primary')
                    ->url(fn ($record) => static::getUrl('view-qr', ['record' => $record]))
                    ->openUrlInNewTab()
                    ->button()
                    ->disabled(fn ($record) => !$record->id),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingDocuments::route('/'),
            'create' => Pages\CreateShippingDocument::route('/create'),
            'edit' => Pages\EditShippingDocument::route('/{record}/edit'),
            'view-qr' => Pages\ViewShippingQr::route('/{record}/view-qr'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function generateAndDisplayShippingQr($shippingDocumentId)
    {
        $shipping = \App\Models\ShippingDocument::with(['invoice', 'supplier', 'products'])->find($shippingDocumentId);

        if (!$shipping) {
            Notification::make()
                ->title('Data Shipping Document tidak ditemukan.')
                ->danger()
                ->send();
            return;
        }

        $data = [
            'id' => $shipping->id,
            'code' => $shipping->code,
            'invoice' => $shipping->invoice?->code,
            'supplier' => $shipping->supplier?->name,
            'status' => $shipping->status,
            'products' => $shipping->products->map(function($p) {
                return [
                    'name' => $p->product?->name ?? '-', // Ambil nama produk dari relasi
                    'quantity' => $p->quantity ?? null,
                ];
            })->values()->all(),
        ];

        $qrContent = json_encode($data);
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(200)->generate($qrContent);

        // Simpan atau tampilkan sesuai kebutuhan, misal tampilkan modal atau simpan ke kolom
        // Contoh: simpan ke kolom qr_code_html_storage
        $shipping->qr_code_html_storage = $qrSvg;
        $shipping->save();
    }
}




