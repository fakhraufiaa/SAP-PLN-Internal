<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Category; //
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Milon\Barcode\DNS1D;
use Illuminate\Support\HtmlString;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 20;

    protected static function generateBarcodeText(string $categoryName): string
    {
        // Get the first 4 characters of the category name, convert to uppercase.
        $categoryCode = Str::upper(Str::substr($categoryName, 0, 4));

        // Generate 10 random digits, padded with leading zeros if necessary.
        $randomNumber = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);

        // Combine them into the desired barcode format.
        return $categoryCode . '-' . $randomNumber;
    }

    protected static function generateBarcodeSvg(string $barcodeText): string
    {
        $barcodeGenerator = new DNS1D();
        // Generate barcode as SVG, Code-128 type, scale 2, height 80
        $svg = $barcodeGenerator->getBarcodeSVG($barcodeText, 'C128', 2, 80);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

     protected static function getBarcodeImageHtml(?string $barcodeValue): string
    {
        if ($barcodeValue) {
            $barcodeSvgData = static::generateBarcodeSvg($barcodeValue);
            return '<img src="' . $barcodeSvgData . '" alt="Barcode" style="width: 100%; max-width: 300px; height: auto; margin-top: 10px; border: 1px solid #ddd; padding: 5px; background-color: #fff; border-radius: 8px;">';
        }
        return '<p style="text-align: center; color: #6b7280; font-size: 0.9em; margin-top: 10px;">Pilih kategori untuk melihat pratinjau barcode.</p>';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('resources.product.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'PRD-'.str_pad((Product::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT))
                    ->readOnly(),
                Forms\Components\TextInput::make('name')
                    ->label(__('resources.product.name'))
                    ->required(),
                Forms\Components\Select::make('category_id')
                    ->label(__('resources.product.category'))
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        // This callback fires when the category is selected or changed.
                        if ($state) {
                            $category = Category::find($state);
                            if ($category) {
                                // Generate and set the barcode text based on the selected category
                                $generatedBarcodeText = static::generateBarcodeText($category->name);
                                $set('barcode', $generatedBarcodeText);
                            } else {
                                // Clear barcode if category not found
                                $set('barcode', null);
                            }
                        } else {
                            // Clear barcode if no category is selected
                            $set('barcode', null);
                        }
                    })
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label(__('resources.category.name'))
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label(__('resources.category.description')),
                            ]),
                Forms\Components\TextInput::make('stock')
                    ->label(__('resources.product.stock'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('barcode')
                    ->label(__('resources.product.barcode'))
                    ->required()
                    ->readOnly()
                    ->Live(),
                   // Placeholder ini yang harus Anda gunakan
                Forms\Components\Placeholder::make('barcode_image_display')
                    ->label(__('resources.product.barcode_image'))
                    ->content(function (callable $get) {
                        $barcodeValue = $get('barcode'); // Retrieve the barcode value from the form state
                        $htmlContent = static::getBarcodeImageHtml($barcodeValue);
                        return new HtmlString($htmlContent); // Wrap the HTML string in HtmlString to prevent escaping
                    })
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label(__('resources.product.description'))
                    ->required()
                    ->columnSpanFull(),
                ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('resources.product.code'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('resources.product.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('resources.product.category'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->label(__('resources.product.stock'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->label(__('resources.product.barcode'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('resources.product.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('resources.product.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

     public static function getGloballySearchableAttributes(): array
    {
        return [
            'code',
            'name',
            'barcode',
        ];
    }

    public static function getModelLabel(): string
    {
        return __('resources.product.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resources.product.label');
    }

    public static function getBreadcrumb(): string
    {
        return __('resources.product.label');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}



