<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Product;

class ScanBarcode extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static string $view = 'filament.pages.scan-barcode';

    protected static ?string $navigationLabel = 'Scan';

    public $scannedCode;
    public $productName;
    public $productCategory;
    public $productSpecification;
    public $shippingInfo = null;

    protected function getScannedCode(): string
    {
        return $this->scannedCode;
    }

    protected function getProductName(): string
    {
        return $this->productName;
    }

    protected function getProductCategory(): string
    {
        return $this->productCategory;
    }

    protected function getProductSpecification(): string
    {
        return $this->productSpecification;
    }

    protected function getListeners(): array
    {
        return [
            'codeScanned' => 'onCodeScanned',
        ];
    }

    public function onCodeScanned(string $code): void
    {
        // Cek apakah hasil scan adalah JSON (QR shipping)
        $data = json_decode($code, true);

        if (is_array($data) && isset($data['id']) && isset($data['code'])) {
            // scannedCode hanya diisi kode shipping saja
            $this->scannedCode = $data['code'];
            // Semua data shipping tetap diisi ke shippingInfo
            $this->shippingInfo = $data;
            // Kosongkan info produk
            $this->productName = null;
            $this->productCategory = null;
            $this->productSpecification = null;
            return;
        }

        // Jika bukan QR shipping, cek produk
        $this->scannedCode = $code;
        $product = Product::where('barcode', $code)->first();

        if ($product) {
            $this->productName = $product->name;
            $this->productCategory = $product->category->name ?? 'Tidak diketahui';
            $this->productSpecification = $product->description ?? '-';
            $this->shippingInfo = null;
        } else {
            $this->productName = 'Produk tidak ditemukan';
            $this->productCategory = '-';
            $this->productSpecification = '-';
            $this->shippingInfo = null;
        }
    }
}
