<?php

namespace App\Filament\Resources\ShippingDocumentResource\Pages;

use App\Filament\Resources\ShippingDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\ShippingDocument;
use Filament\Notifications\Notification;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class CreateShippingDocument extends CreateRecord
{
    protected static string $resource = ShippingDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Aksi header untuk halaman Create
        ];
    }

    /**
     * Metode Livewire publik untuk menghasilkan dan menampilkan QR Code.
     * Ini dipanggil oleh tombol "Generate QR Code".
     *
     * @param string $shippingDocumentCode Kode dokumen pengiriman.
     * @return void
     */
    public function generateAndDisplayShippingQr(string $shippingDocumentCode): void
    {
        // Di halaman 'create', record belum ada di database.
        // Kita hanya bisa generate QR dari record yang sudah tersimpan.
        // Tombol generate sudah di-disable di resource jika record belum ada.
        $shippingDocument = ShippingDocument::where('code', $shippingDocumentCode)->first();

        if (!$shippingDocument) {
            Notification::make()
                ->title('Dokumen Belum Tersedia')
                ->body('Dokumen Pengiriman perlu disimpan terlebih dahulu untuk menghasilkan QR Code. Silakan simpan dokumen ini, lalu buka kembali di halaman edit untuk fitur QR.')
                ->danger()
                ->send();
            return;
        }

        try {
            $qrData = $shippingDocument->getQrCodeData(); // Ambil data JSON untuk QR

            // Hasilkan QR Code sebagai SVG string
            $qrSvg = QrCode::size(200)->generate($qrData);

            // Setel nilai ke hidden field agar dapat dipertahankan di form
            // $this->data adalah array yang merepresentasikan state form Livewire
            $this->data['qr_code_html_storage'] = $qrSvg;

            // Dispatch event ke browser untuk menampilkan QR
            $this->dispatch('display-shipping-qr', qrSvgHtml: $qrSvg);

            Notification::make()
                ->title('QR Code Dibuat')
                ->body('QR Code berhasil dibuat dan ditampilkan. Harap simpan dokumen ini.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Gagal menghasilkan QR code untuk dokumen pengiriman (Create): ' . $e->getMessage(), ['code' => $shippingDocumentCode]);
            Notification::make()
                ->title('Gagal Membuat QR')
                ->body('Terjadi kesalahan saat membuat QR Code: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Metode downloadShippingQr akan ditambahkan nanti
}
