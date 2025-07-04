<?php

namespace App\Filament\Resources\WorkOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkOrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_type')
                    ->label('Jenis Item')
                    ->options([
                        'tech_req' => 'Technical Requirement',
                        'task' => 'Task',
                        'material' => 'Material',
                        'method' => 'Method',
                        'machine' => 'Machine',
                    ])
                    ->required()
                    ->native(false) // Untuk tampilan dropdown yang lebih baik
                    ->columnSpanFull(), // Agar mengambil lebar penuh di form

                Forms\Components\Textarea::make('item_description')
                    ->label('Deskripsi Item')
                    ->required()
                    ->maxLength(65535) // Menggunakan TEXT, jadi bisa lebih panjang
                    ->rows(3) // Memberikan tinggi awal 3 baris
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('unit')
                    ->label('Unit (Opsional)')
                    ->maxLength(255)
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Menggunakan 'item_description' sebagai recordTitleAttribute
            // Ini akan ditampilkan di modal konfirmasi delete, dll.
            ->recordTitleAttribute('item_description')
            ->columns([
                // Menampilkan kolom-kolom baru
                Tables\Columns\TextColumn::make('item_type')
                    ->label('Jenis Item')
                    ->badge() // Menampilkan sebagai badge agar lebih visual
                    ->color(fn (string $state): string => match ($state) {
                        'tech_req' => 'info',
                        'task' => 'success',
                        'material' => 'warning',
                        'method' => 'primary',
                        'machine' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(), // Memungkinkan pencarian berdasarkan jenis item

                Tables\Columns\TextColumn::make('item_description')
                    ->label('Deskripsi')
                    ->wrap() // Agar teks panjang bisa wrap
                    ->searchable(), // Memungkinkan pencarian berdasarkan deskripsi

                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->toggleable(isToggledHiddenByDefault: true) // Bisa disembunyikan secara default
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan secara default

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), // Sembunyikan secara default
            ])
            ->filters([
                // Filter berdasarkan item_type
                Tables\Filters\SelectFilter::make('item_type')
                    ->label('Filter Jenis Item')
                    ->options([
                        'tech_req' => 'Technical Requirement',
                        'item' => 'Item',
                        'task' => 'Task',
                        'material' => 'Material',
                        'method' => 'Method',
                        'machine' => 'Machine',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
