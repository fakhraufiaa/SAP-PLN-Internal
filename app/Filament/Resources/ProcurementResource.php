<?php

namespace App\Filament\Resources;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProcurementResource\Pages;
use App\Filament\Resources\ProcurementResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\ProcurementResource\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\ProcurementResource\RelationManagers\PurchasesRelationManager;
use App\Models\Procurement;
use App\Models\WorkOrder; // Corrected casing for WorkOrder model
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Contract;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Storage;

class ProcurementResource extends Resource
{
    protected static ?string $model = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $navigationGroup = 'Procurement';

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('resources.procurement.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.procurement.label');
    }

    public static function getBreadcrumb(): string
    {
        return __('resources.procurement.label');
    }

    public static function getNavigationLabel(): string
    {
        return __('resources.procurement.label');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resources.procurement.documents'))
                    ->schema([
                        Forms\Components\FileUpload::make('dkmj_document')
                            ->label(__('Input File DKMJ'))
                            ->helperText('Upload dokumen DKMJ dalam format PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('public') // Explicitly set the disk to public
                            ->directory('procurement-dkmj-documents')
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->openable()
                            ->previewable(true)
                            // Custom file naming based on procurement code
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file, callable $get) {
                                    $code = $get('code');
                                    // Ensure $code is not null or empty to avoid strange file names
                                    return $code ? "DKMJ_{$code}.pdf" : "DKMJ_" . uniqid() . ".pdf";
                                }
                            )
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\TextInput::make('code')
                    ->label(__('resources.procurement.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'PRC-'.str_pad((Procurement::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT))
                    ->readOnly(),

                // START: Modified 'number' field to be a Select for WorkOrder
                // This field will store the WorkOrder ID in the 'number' column of Procurement
                Forms\Components\Select::make('number')
                    ->label(__('resources.procurement.number'))
                    // Use the relationship to display 'no_wo' and store the WorkOrder 'id'
                    ->relationship('workOrder', 'no_wo')
                    ->searchable()
                    ->preload() // Optional: load all options initially for smaller datasets
                    ->required() // The foreign key 'number' is required
                    ->live() // Make it live to trigger updates on other fields
                    ->afterStateUpdated(function ($state, callable $set) {
                        // When a WorkOrder is selected, update other fields based on its data
                        if ($state) {
                            $workOrder = WorkOrder::find($state);
                            if ($workOrder) {
                                $set('amp_id', $workOrder->amp_id);
                                // Assuming 'penugasan_id' in Procurement maps to 'nama_penugasan' in WorkOrder
                                $set('penugasan_id', $workOrder->nama_penugasan);
                                $set('nilai_penugasan', $workOrder->nilai_penugasan);
                                $set('start_date', $workOrder->tgl_penugasan);
                                $set('end_date', $workOrder->tgl_bts_penugasan);
                            }
                        } else {
                            // Clear related fields if the selection is cleared
                            $set('amp_id', null);
                            $set('penugasan_id', null);
                            $set('nilai_penugasan', null);
                            $set('start_date', null);
                            $set('end_date', null);
                        }
                    })
                    ->afterStateHydrated(function ($state, $record, callable $set) {
                        if ($record && $state) {
                            $workOrder = WorkOrder::find($state);
                            if ($workOrder) {
                                $set('amp_id', $workOrder->amp_id);
                                $set('penugasan_id', $workOrder->nama_penugasan);
                                $set('nilai_penugasan', $workOrder->nilai_penugasan);
                                $set('start_date', $workOrder->tgl_penugasan);
                                $set('end_date', $workOrder->tgl_bts_penugasan);
                            }
                        }
                    }),
                // END: Modified 'number' field

                // These fields will now be populated by the Select's afterStateUpdated/Hydrated
                // They should be readOnly() if they are purely derived from WorkOrder and not directly editable
                Forms\Components\TextInput::make('amp_id')
                    ->label(__('resources.procurement.amp_id'))
                    ->readOnly() // Make it read-only as it's derived from WorkOrder
                    ->dehydrated(true), // Ensure it's saved to the database
                Forms\Components\TextInput::make('penugasan_id')
                    ->label(__('resources.procurement.penugasan_id'))
                    ->readOnly()
                    ->dehydrated(true),
                Forms\Components\TextInput::make('kategori')
                    ->label(__('resources.procurement.kategori'))
                    ->required(),// Assuming this is also derived from WorkOrder
                Forms\Components\TextInput::make('nilai_penugasan')
                    ->label(__('resources.procurement.nilai_penugasan'))
                    ->readOnly()
                    ->numeric()
                    ->dehydrated(true),
                Forms\Components\DatePicker::make('start_date')
                    ->label(__('resources.procurement.start_date'))
                    ->readOnly()
                    ->dehydrated(true),
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('resources.procurement.end_date'))
                    ->readOnly()
                    ->dehydrated(true),
                Forms\Components\Select::make('status')
                    ->label(__('resources.procurement.status'))
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
                    ->label(__('resources.procurement.code'))
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
                Tables\Columns\TextColumn::make('workOrder.no_wo') // This is correct for displaying no_wo in the table
                    ->label(__('resources.procurement.number'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('amp_id')
                    ->label(__('resources.procurement.amp_id'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('penugasan_id')
                    ->label(__('resources.procurement.penugasan_id'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kategori')
                    ->label(__('resources.procurement.kategori'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nilai_penugasan')
                    ->label(__('resources.procurement.nilai_penugasan'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('resources.procurement.start_date'))
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('resources.procurement.end_date'))
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_at')
                    ->label(__('resources.procurement.status_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resources.procurement.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resources.procurement.updated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label(__('resources.procurement.deleted_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('dkmj_document')
                    ->label('File DKMJ')
                    ->formatStateUsing(function ($state, $record) {
                        if (empty($state)) {
                            return '-';
                        }
                        return "DKMJ_{$record->code}.pdf";
                    })
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(fn ($record) => $record->dkmj_document ? Storage::disk('public')->url($record->dkmj_document) : null)
                    ->openUrlInNewTab()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('contract')
                    ->label(__('resources.procurement.contract'))
                    ->relationship('contract', 'id')
                    ->getOptionLabelFromRecordUsing(fn (Contract $record) => "Contract #{$record->id}")
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_purchases')
                    ->label(__('resources.procurement.view_purchases'))
                    ->icon('heroicon-o-shopping-cart')
                    ->url(fn (Procurement $record): string =>
                        PurchaseResource::getUrl('index', ['tableFilters[procurement][value]' => $record->id]))
                    ->openUrlInNewTab(),
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

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
            PurchasesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcurements::route('/'),
            'create' => Pages\CreateProcurement::route('/create'),
            'edit' => Pages\EditProcurement::route('/{record}/edit'),
        ];
    }
}
