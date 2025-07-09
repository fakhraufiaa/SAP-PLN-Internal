<?php

namespace App\Filament\Resources\WorkOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get; // Tetap import ini jika ada komponen lain yang menggunakannya

// Pastikan Anda mengimpor model Procurement dan Enum ProductStatus
use App\Models\Procurement;
use App\Models\WorkOrder;
use App\Enums\ProductStatus;

class ProcurementRelationManager extends RelationManager
{
    protected static string $relationship = 'procurements';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('resources.procurement.documents'))
                    ->schema([
                        Forms\Components\FileUpload::make('dkmj_document')
                            ->label(__('Input File DKMJ'))
                            ->helperText('Upload dokumen DKMJ dalam format PDF')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('public')
                            ->directory('procurement-dkmj-documents')
                            ->maxSize(10240) // 10MB
                            ->downloadable()
                            ->openable()
                            ->previewable(true)
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file, callable $get) {
                                    $code = $get('code');
                                    $fileName = $code ? "DKMJ_{$code}.pdf" : "DKMJ_" . uniqid() . ".pdf";
                                    return $fileName;
                                }
                            )
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                // Gunakan accessor 'work_order_no' untuk menampilkan no_wo
                Forms\Components\hidden::make('work_order_no') // Bind ke accessor
                    ->label(__('resources.procurement.number')), // Label yang sesuai


                // Field 'number' yang sebenarnya (FK) tidak perlu ditampilkan
                // karena RelationManager secara otomatis mengisinya dengan ID WorkOrder
                // saat membuat record baru, dan sudah ada saat mengedit.

                Forms\Components\TextInput::make('code')
                    ->label(__('resources.procurement.code'))
                    ->required()
                    ->unique(ignoreRecord: true, table: Procurement::class)
                    ->default(fn () => 'PRC-'.str_pad((Procurement::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT))
                    ->readOnly(),
                Forms\Components\TextInput::make('amp_id')
                    ->label(__('resources.procurement.amp_id'))
                    ->readOnly()
                    ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->amp_id),
                Forms\Components\TextInput::make('penugasan_id')
                    ->label(__('resources.procurement.penugasan_id'))
                    ->readOnly()
                    ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->nama_penugasan),
                Forms\Components\TextInput::make('kategori')
                    ->label(__('resources.procurement.kategori'))
                    ->required(),
                Forms\Components\TextInput::make('nilai_penugasan')
                    ->label(__('resources.procurement.nilai_penugasan'))
                    ->readOnly()
                    ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->nilai_penugasan),
                Forms\Components\DatePicker::make('start_date')
                    ->label(__('resources.procurement.start_date'))
                    ->readOnly()
                    ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->tgl_penugasan),
                Forms\Components\DatePicker::make('end_date')
                    ->label(__('resources.procurement.end_date'))
                    ->readOnly()
                    ->default(fn (RelationManager $livewire) => $livewire->ownerRecord->tgl_bts_penugasan),
                Forms\Components\Select::make('status')
                    ->label(__('resources.procurement.status'))
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
                    ->label(__('resources.procurement.code'))
                    ->searchable()
                    ->sortable(),
                // Tampilkan no_wo dari WorkOrder terkait di tabel
                Tables\Columns\TextColumn::make('workOrder.no_wo') // Ini akan bekerja jika relasi di model Procurement benar
                    ->label(__('resources.procurement.number')) // Label yang lebih deskriptif
                    ->sortable()
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
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('resources.procurement.start_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('resources.procurement.end_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('resources.procurement.status'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                // Tambahkan filter jika diperlukan
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
