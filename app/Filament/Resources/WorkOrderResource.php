<?php

namespace App\Filament\Resources;


use App\Filament\Resources\WorkOrderResource\Pages;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Enums\ProductStatus;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Procurement';

    protected static ?int $navigationSort = 50;


    public static function getModelLabel(): string
    {
        return __('resources.workOrder.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.workOrder.label');
    }

    public static function getBreadcrumb(): string
    {
        return __('resources.workOrder.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resources.workOrder.label');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\TextInput::make('code')
                //     ->label('Kode')
                //     ->required()
                //     ->unique(ignoreRecord: true)
                //     ->default(fn () => 'WO-'.str_pad((WorkOrder::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT))
                //     ->readOnly(),
                Forms\Components\TextInput::make('no_wo')
                    ->label(__('resources.workOrder.no_wo'))
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('no_surat')
                    ->label(__('resources.workOrder.no_surat'))
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('no_wbs')
                    ->label(__('resources.workOrder.no_wbs'))
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('amp_id')
                    ->label(__('resources.workOrder.amp_id'))
                    ->required(),
                Forms\Components\TextInput::make('nama_penugasan')
                    ->label(__('resources.workOrder.nama_penugasan'))
                    ->required(),
                Forms\Components\TextInput::make('kategori')
                    ->label(__('resources.workOrder.kategori'))
                    ->required(),
                Forms\Components\TextInput::make('nilai_penugasan')
                    ->label(__('resources.workOrder.nilai_penugasan'))
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('tgl_penugasan')
                    ->label(__('resources.workOrder.start_date'))
                    ->required(),
                Forms\Components\DatePicker::make('tgl_bts_penugasan')
                    ->label(__('resources.workOrder.end_date'))
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('resources.workOrder.status'))
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
                Tables\Columns\TextColumn::make('no_wo')->label(__('resources.workOrder.no_wo'))->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'canceled' => 'danger',
                        'pending' => 'warning',
                        'done' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_surat')->label(__('resources.workOrder.no_surat'))->searchable(),
                Tables\Columns\TextColumn::make('no_wbs')->label(__('resources.workOrder.no_wbs'))->searchable(),
                Tables\Columns\TextColumn::make('amp_id')->label(__('resources.workOrder.amp_id'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nama_penugasan')->label(__('resources.workOrder.nama_penugasan'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kategori')->label(__('resources.workOrder.kategori'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('nilai_penugasan')->label(__('resources.workOrder.nilai_penugasan'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tgl_penugasan')->label(__('resources.workOrder.tgl_penugasan'))->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('tgl_bts_penugasan')->label(__('resources.workOrder.tgl_bts_penugasan'))->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('status_at')->label('Status At')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')->label('Dihapus')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            WorkOrderResource\RelationManagers\WorkOrderItemsRelationManager::class,
        ];
    }
}
