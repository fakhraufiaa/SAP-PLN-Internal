<?php

namespace App\Filament\Resources\PurchaseResource\RelationManagers;

use App\Enums\ProductStatus;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Procurement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Faktur';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('resources.invoice.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'INV-'.str_pad((Invoice::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT))
                    ->readOnly(),
                 Forms\Components\Select::make('purchase_code')
                    ->label(__('resources.invoice.purchase'))
                    ->options(function () {
                        return \App\Models\Purchase::with('procurement')
                            ->get()
                            ->mapWithKeys(function ($purchase) {
                                return [
                                    $purchase->code => $purchase->code,
                                ];
                            });
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                        $purchase = \App\Models\Purchase::with('procurement')->where('code', $state)->first();
                        if ($purchase) {
                            $set('purchase_id', $purchase->id);
                            $set('penugasan_id_display', $purchase->procurement?->penugasan_id ?? '-');
                            $set('number', $purchase->procurement?->id ?? null);
                        }
                    })
                    ->afterStateHydrated(function ($state, $record, \Filament\Forms\Set $set) {
                        if ($record && $record->purchase) {
                            $set('purchase_code', $record->purchase->code);
                            $set('penugasan_id_display', $record->purchase->procurement?->penugasan_id ?? '-');
                            $set('number', $record->purchase->procurement?->id ?? null);
                        }
                    }),

                Forms\Components\Hidden::make('number')
                    ->required(),

                Forms\Components\TextInput::make('penugasan_id_display')
                    ->label(__('Penugasan'))
                    ->disabled()
                    ->dehydrated(false),

                Forms\Components\DatePicker::make('date')
                    ->label(__('resources.invoice.date'))
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('supplier_id')
                    ->label(__('resources.invoice.supplier'))
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable()
                    ->default(fn ($livewire) => $livewire->ownerRecord->supplier_id)
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
                    ->label(__('resources.invoice.status'))
                    ->options(ProductStatus::class)
                    ->enum(ProductStatus::class)
                    ->default(ProductStatus::PENDING)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('resources.invoice.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('procurement.code')
                    ->label(__('resources.invoice.number'))
                    ->formatStateUsing(function ($record) {
                        return $record->purchase?->procurement?->code ?? '-';
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('resources.invoice.date'))
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('resources.invoice.supplier'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase.code')
                    ->label(__('resources.invoice.purchase'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('resources.invoice.status'))
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::CANCELED => 'danger',
                        ProductStatus::PENDING => 'warning',
                        ProductStatus::DONE => 'success',
                    })
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->getLabel())
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_at')
                    ->label(__('resources.invoice.status_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resources.invoice.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resources.invoice.updated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('resources.invoice.deleted_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('supplier')
                    ->label(__('resources.invoice.supplier'))
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('edit', ['record' => $record]))
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
