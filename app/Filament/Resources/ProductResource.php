<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Category; // Pastikan model Category terimpor
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Milon\Barcode\DNS1D;
use Illuminate\Support\HtmlString;
use Picqer\Barcode\BarcodeGeneratorJPG; // Import Picqer Barcode Generator untuk JPG
use Filament\Notifications\Notification; // Import Notification
use Filament\Forms\Get; // Import Get untuk Closure form

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 20;

    /**
     * Menghasilkan teks barcode berdasarkan nama kategori.
     *
     * @param string $categoryName Nama kategori.
     * @return string Teks barcode yang dihasilkan.
     */
    protected static function generateBarcodeText(string $categoryName): string
    {
        // Ambil 4 karakter pertama dari nama kategori, ubah ke huruf besar.
        $categoryCode = Str::upper(Str::substr($categoryName, 0, 4));

        // Hasilkan 5 digit angka acak, tambahkan nol di depan jika perlu.
        $randomNumber = str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT);

        // Gabungkan menjadi format barcode yang diinginkan.
        return $categoryCode . '-' . $randomNumber;
    }

    /**
     * Menghasilkan data SVG barcode.
     *
     * @param string $barcodeText Teks barcode.
     * @return string Data URI SVG barcode.
     */
    protected static function generateBarcodeSvg(string $barcodeText): string
    {
        $barcodeGenerator = new DNS1D();
        // Hasilkan barcode sebagai SVG, tipe Code-128, skala 2, tinggi 80
        $svg = $barcodeGenerator->getBarcodeSVG($barcodeText, 'C128', 2, 80);
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Menghasilkan HTML untuk menampilkan gambar barcode.
     *
     * @param string|null $barcodeValue Nilai barcode.
     * @return string HTML string dari gambar barcode atau pesan placeholder.
     */
    protected static function getBarcodeImageHtml(?string $barcodeValue): string
    {
        if ($barcodeValue) {
            $barcodeSvgData = static::generateBarcodeSvg($barcodeValue);
            return '<img src="' . $barcodeSvgData . '" alt="Barcode" style="width: 100%; max-width: 300px; height: auto; margin-top: 10px; border: 1px solid #ddd; padding: 5px; background-color: #fff; border-radius: 8px;">';
        }
        return '<p style="text-align: center; color: #6b7280; font-size: 0.9em; margin-top: 10px;">Pilih kategori untuk melihat pratinjau barcode.</p>';
    }

    /**
     * Mendefinisikan skema formulir untuk sumber daya ini.
     *
     * @param Form $form Objek Form.
     * @return Form Skema formulir yang telah dikonfigurasi.
     */
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label(__('resources.product.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'PRD-' . str_pad((Product::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT))
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
                        // Callback ini dijalankan saat kategori dipilih atau diubah.
                        if ($state) {
                            $category = Category::find($state);
                            if ($category) {
                                // Hasilkan dan atur teks barcode berdasarkan kategori yang dipilih
                                $generatedBarcodeText = static::generateBarcodeText($category->name);
                                $set('barcode', $generatedBarcodeText);
                            } else {
                                // Hapus barcode jika kategori tidak ditemukan
                                $set('barcode', null);
                            }
                        } else {
                            // Hapus barcode jika tidak ada kategori yang dipilih
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
                    ->live(), // Penting untuk memperbarui placeholder secara real-time

                // Mengelompokkan gambar barcode dan tombol unduh menggunakan Group dengan flexbox
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Placeholder::make('barcode_image_display')
                            ->label(__('resources.product.barcode_image'))
                            ->content(function (Get $get) {
                                $barcodeValue = $get('barcode'); // Ambil nilai barcode dari state form
                                $htmlContent = static::getBarcodeImageHtml($barcodeValue);
                                return new HtmlString($htmlContent); // Bungkus string HTML dengan HtmlString untuk mencegah escaping
                            }),

                        // Tombol unduh baru untuk gambar barcode JPG
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('download_barcode_jpg')
                                ->label('Unduh JPG')
                                ->color('primary')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->requiresConfirmation() // Opsional: meminta konfirmasi sebelum mengunduh
                                ->action(function (Forms\Components\Actions\Action $action, Get $get) {
                                    $barcodeValue = $get('barcode'); // Ambil nilai barcode dari state form

                                    if (empty($barcodeValue)) {
                                        Notification::make()
                                            ->title('Barcode Kosong')
                                            ->body('Mohon masukkan nilai barcode terlebih dahulu.')
                                            ->danger()
                                            ->send();
                                        return; // Hentikan eksekusi jika barcode kosong
                                    }

                                    // Panggil metode Livewire publik pada komponen Livewire halaman
                                    // Menggunakan $action->getLivewire() untuk mendapatkan instance Livewire yang benar.
                                    $action->getLivewire()->downloadBarcodeJpg($barcodeValue);
                                })
                                // Tombol hanya terlihat jika ada nilai barcode yang valid
                                ->visible(fn (Get $get) => !empty($get('barcode'))),
                        ])
                        ->alignCenter(), // Menjaga posisi vertikal di tengah kolom
                    ])
                    ->columnSpanFull() // Pastikan grup mengisi seluruh lebar formulir
                    // Terapkan kelas flexbox langsung ke Group
                    ->extraAttributes([
                        'class' => 'flex items-center space-x-10 mt-4' // flex untuk tata letak horizontal, items-center untuk penyelarasan vertikal, space-x-10 untuk celah horizontal, mt-4 untuk sedikit geser ke bawah
                    ]),

                Forms\Components\Textarea::make('description')
                    ->label(__('resources.product.description'))
                    ->required()
                    ->columnSpanFull(),

                // === TAMBAHKAN SCRIPT JAVASCRIPT INI DI SINI ===
                Forms\Components\View::make('scripts.barcode-download-script') // Buat view Blade baru
                    ->hiddenLabel() // Sembunyikan label untuk komponen view ini
            ]);
    }

    /**
     * Tabel untuk tampilan daftar produk.
     *
     * @param Table $table Objek Table.
     * @return Table Skema tabel yang telah dikonfigurasi.
     */
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

    /**
     * Mengembalikan relasi yang akan dimuat untuk sumber daya ini.
     *
     * @return array Array relasi.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Mengembalikan daftar halaman yang terkait dengan sumber daya ini.
     *
     * @return array Array halaman.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    /**
     * Mengembalikan atribut yang dapat dicari secara global.
     *
     * @return array Array atribut yang dapat dicari.
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'code',
            'name',
            'barcode',
        ];
    }

    /**
     * Mengembalikan label model tunggal.
     *
     * @return string Label model.
     */
    public static function getModelLabel(): string
    {
        return __('resources.product.label');
    }

    /**
     * Mengembalikan label model jamak.
     *
     * @return string Label model jamak.
     */
    public static function getPluralModelLabel(): string
    {
        return __('resources.product.label');
    }

    /**
     * Mengembalikan breadcrumb untuk sumber daya ini.
     *
     * @return string Breadcrumb.
     */
    public static function getBreadcrumb(): string
    {
        return __('resources.product.label');
    }

    /**
     * Mengembalikan query Eloquent untuk sumber daya ini,
     * menghilangkan global scope SoftDeletingScope.
     *
     * @return Builder Query Eloquent yang telah dimodifikasi.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
