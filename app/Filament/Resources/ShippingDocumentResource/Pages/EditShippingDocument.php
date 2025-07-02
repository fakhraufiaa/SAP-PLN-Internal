<?php

namespace App\Filament\Resources\ShippingDocumentResource\Pages;

use App\Filament\Resources\ShippingDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification; // Tambahkan di bagian use
use SimpleSoftwareIO\QrCode\Facades\QrCode; // pastikan sudah install package qrcode

class EditShippingDocument extends EditRecord
{
    protected static string $resource = ShippingDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('generate_qr')
                ->label('Generate QR Code')
                ->icon('heroicon-o-qr-code')
                ->color('primary')
                ->visible(fn ($record) => $record && $record->id)
                ->action(function ($record) {
                    ShippingDocumentResource::generateAndDisplayShippingQr($record->id);

                    Notification::make()
                        ->title('QR Code berhasil digenerate!')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * Metode Livewire publik untuk menghasilkan dan menampilkan QR Code.
     * Ini dipanggil oleh tombol "Generate QR Code".
     *
     * @param string $shippingDocumentCode Kode dokumen pengiriman.
     * @return void
     */
    public function generateAndDisplayShippingQr($shippingDocumentId)
    {
        $shipping = \App\Models\ShippingDocument::with(['invoice', 'supplier', 'products'])->find($shippingDocumentId);

        if (!$shipping) {
            $this->form->fill(['qr_code_html_storage' => null]);
            Notification::make()
                ->title('Data Shipping Document tidak ditemukan.')
                ->danger()
                ->send();
            return;
        }

        // Ambil data yang ingin dimasukkan ke QR
        $data = [
            'id' => $shipping->id,
            'code' => $shipping->code,
            'invoice' => $shipping->invoice?->code,
            'supplier' => $shipping->supplier?->name,
            'status' => $shipping->status,
            'products' => $shipping->products->map(function($p) {
                return [
                    'name' => $p->name,
                    'qty' => $p->pivot->qty ?? null,
                ];
            })->values()->all(),
            // Tidak ada suratJalan_document di sini
        ];

        $qrContent = json_encode($data);

        // Generate QR code SVG
        $qrSvg = QrCode::format('svg')->size(200)->generate($qrContent);

        // Simpan SVG ke form state
        $this->form->fill(['qr_code_html_storage' => $qrSvg]);

        Notification::make()
            ->title('QR Code berhasil digenerate!')
            ->success()
            ->send();
    }

    // Metode downloadShippingQr akan ditambahkan nanti
}
