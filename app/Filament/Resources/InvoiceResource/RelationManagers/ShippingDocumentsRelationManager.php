<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Enums\ProductStatus;
use App\Filament\Resources\ShippingDocumentResource;
use App\Models\ShippingDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShippingDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'shippingDocuments';

    protected static ?string $title = 'Surat Jalan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('resources.shipping_document.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'SHP-'.str_pad((ShippingDocument::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT))
                    ->readOnly(),

                Forms\Components\Select::make('invoice_id')
                    ->label(__('resources.shipping_document.invoice'))
                    ->options(function () {
                        return \App\Models\Invoice::pluck('code', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                        $invoice = \App\Models\Invoice::with('purchase.procurement')->find($state);
                        if ($invoice && $invoice->purchase && $invoice->purchase->procurement) {
                            $set('number', $invoice->purchase->procurement->number); // simpan penugasan_id ke kolom number
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
                    }),

                Forms\Components\Hidden::make('number')
                    ->required(),

                Forms\Components\TextInput::make('penugasan_id_display')
                    ->label('Penugasan ID')
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\Select::make('supplier_id')
                    ->label(__('resources.shipping_document.supplier'))
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->default(function ($livewire) {
                        return $livewire->getOwnerRecord()->supplier_id;
                    })
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label(__('resources.supplier.name'))
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Section::make(__('resources.supplier.sales_contact'))
                            ->schema([
                                Forms\Components\TextInput::make('sales_name')
                                    ->label(__('resources.supplier.sales_name'))
                                    ->required(),
                                Forms\Components\TextInput::make('sales_phone')
                                    ->label(__('resources.supplier.sales_phone'))
                                    ->required()
                                    ->tel(),
                                Forms\Components\TextInput::make('sales_email')
                                    ->label(__('resources.supplier.sales_email'))
                                    ->email(),
                            ])->columns(3),
                        Forms\Components\Section::make(__('resources.supplier.logistics_contact'))
                            ->schema([
                                Forms\Components\TextInput::make('logistics_name')
                                    ->label(__('resources.supplier.logistics_name'))
                                    ->required(),
                                Forms\Components\TextInput::make('logistics_phone')
                                    ->label(__('resources.supplier.logistics_phone'))
                                    ->required()
                                    ->tel(),
                                Forms\Components\TextInput::make('logistics_email')
                                    ->label(__('resources.supplier.logistics_email'))
                                    ->email(),
                            ])->columns(3),
                    ]),
                Forms\Components\Select::make('status')
                    ->label(__('resources.shipping_document.status'))
                    ->options(ProductStatus::class)
                    ->enum(ProductStatus::class)
                    ->default(ProductStatus::PENDING)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('resources.shipping_document.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('number')
                    ->label(__('resources.shipping_document.number'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.code')
                    ->label(__('resources.shipping_document.invoice'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('resources.shipping_document.supplier'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('resources.shipping_document.status'))
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::CANCELED => 'danger',
                        ProductStatus::PENDING => 'warning',
                        ProductStatus::DONE => 'success',
                    })
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->getLabel())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (ShippingDocument $record): string => ShippingDocumentResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-m-pencil-square')
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    protected function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

