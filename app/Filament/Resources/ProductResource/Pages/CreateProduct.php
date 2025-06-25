<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord; // Menggunakan CreateRecord
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;
use Picqer\Barcode\BarcodeGeneratorJPG; // Import Picqer Barcode Generator untuk JPG
use Filament\Notifications\Notification; // Import Notification
use Milon\Barcode\DNS1D; // Import DNS1D untuk generate barcode SVG
use Illuminate\Support\Facades\Log; // Import untuk logging

class CreateProduct extends CreateRecord // Pastikan ini meng-extend CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aksi header untuk halaman Create (mungkin tidak ada aksi khusus di sini secara default)
        ];
    }

    /**
     * Metode Livewire publik untuk menangani unduhan barcode JPG.
     * Metode ini akan dipanggil oleh aksi Filament dari form yang didefinisikan di ProductResource.
     *
     * @param string|null $barcodeValue Nilai barcode yang akan diunduh.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|void
     */
     public function downloadBarcodeJpg(?string $barcodeValue): void
    {
        if (empty($barcodeValue)) {
            Notification::make()
                ->title('Error Unduh')
                ->body('Mohon masukkan nilai barcode terlebih dahulu.')
                ->danger()
                ->send();
            return;
        }

        try {
            $generator = new BarcodeGeneratorJPG();
            // Sesuaikan TYPE_CODE_128 dengan jenis barcode yang Anda gunakan
            $barcodeImage = $generator->getBarcode($barcodeValue, $generator::TYPE_CODE_128, 2, 40);

            // Encode gambar barcode ke base64
            $base64Image = base64_encode($barcodeImage);
            $filename = 'barcode-' . $barcodeValue . '.jpg';

            // Dispatch event browser dengan data base64 dan nama file
            // JavaScript di sisi klien (barcode-download-script.blade.php) akan menangani unduhan
            $this->dispatch('download-barcode-jpg',
                base64Data: $base64Image,
                fileName: $filename
            );

            Notification::make()
                ->title('Mengunduh Barcode')
                ->body('Barcode sedang diunduh. Mohon tunggu sebentar.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            // Log kesalahan untuk debugging lebih lanjut di sisi server
            Log::error('Gagal menghasilkan atau dispatch barcode: ' . $e->getMessage(), ['barcode_value' => $barcodeValue]);

            Notification::make()
                ->title('Unduhan Gagal')
                ->body('Tidak dapat menghasilkan atau mengunduh gambar barcode. Silakan coba lagi. Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }


    // ... metode lain yang mungkin ada di CreateProduct Anda, seperti getFormSchema() jika didefinisikan di sini
}
