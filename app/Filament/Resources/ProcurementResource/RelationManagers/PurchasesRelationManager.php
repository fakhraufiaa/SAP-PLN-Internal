<?php

namespace App\Filament\Resources\ProcurementResource\RelationManagers;

use App\Models\Purchase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\PurchaseResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\ProductStatus;

class PurchasesRelationManager extends RelationManager
{
    protected static string $relationship = 'purchases';

    protected static ?string $title = 'Pembelian';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('resources.purchase.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'PUR-'.str_pad((Purchase::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT))
                    ->readOnly(),
               Forms\Components\Select::make('number')
                    ->label(__('resources.purchase.number'))
                    ->searchable()
                    ->options(function () {
                        return \App\Models\Procurement::pluck('code', 'id');
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                        if ($state) {
                            $procurement = \App\Models\Procurement::find($state);
                            if ($procurement) {
                                $set('procurement_id', $procurement->id);
                                $set('procurement_id_display', $procurement->penugasan_id);
                            }
                        } else {
                            $set('procurement_id', null);
                            $set('procurement_id_display', null);
                        }
                    })
                    ->afterStateHydrated(function ($state, $record, \Filament\Forms\Set $set) {
                        if ($record && $record->number) {
                            $procurement = \App\Models\Procurement::find($record->number);
                            $set('procurement_id', $procurement?->id ?? null);
                            $set('procurement_id_display', $procurement?->penugasan_id ?? null);
                        }
                    })
                    ->required(),

                Forms\Components\Hidden::make('number')
                    ->required(),

                Forms\Components\Hidden::make('procurement_id')
                    ->required(),

                Forms\Components\TextInput::make('procurement_id_display')
                    ->label(__('resources.purchase.procurement'))
                    ->disabled()
                    ->dehydrated(false),


                Forms\Components\Select::make('supplier_id')
                ->label(__('resources.purchase.supplier'))
                ->relationship('supplier', 'name')
                ->required()
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return \App\Models\Supplier::where('name', 'like', "%{$search}%")
                        ->limit(20)
                        ->pluck('name', 'id');
                })
                ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Supplier::find($value)?->name)
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


                Forms\Components\DatePicker::make('purchase_date')
                    ->label(__('resources.purchase.purchase_date'))
                    ->default(fn ($livewire) => $livewire->ownerRecord->purchase_date)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('resources.purchase.status'))
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
                    ->label(__('resources.purchase.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                ->label(__('resources.procurement.status'))
                ->badge()
                ->color(fn (ProductStatus $state): string => match ($state) {
                    ProductStatus::CANCELED => 'danger',
                    ProductStatus::PENDING => 'warning',
                    ProductStatus::DONE => 'success',
                })
                ->formatStateUsing(fn (ProductStatus $state): string => $state->getLabel())
                ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('resources.purchase.supplier'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label(__('resources.purchase.purchase_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('resources.procurement.status'))
                    ->badge()
                    ->color(fn (ProductStatus $state): string => match ($state) {
                        ProductStatus::CANCELED => 'danger',
                        ProductStatus::PENDING => 'warning',
                        ProductStatus::DONE => 'success',
                    })
                    ->formatStateUsing(fn (ProductStatus $state): string => $state->getLabel())
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_at')
                    ->label(__('resources.procurement.status_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resources.purchase.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resources.purchase.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('resources.purchase.deleted_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('supplier')
                    ->label(__('resources.purchase.supplier'))
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Purchase $record): string => PurchaseResource::getUrl('edit', ['record' => $record]))
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
