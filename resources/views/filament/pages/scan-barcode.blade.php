<x-filament-panels::page>
        <div class="space-y-8">
            <x-filament::section>
                <div class="space-y-6">
                    <div id="reader"
                    wire:ignore
                        class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden shadow-sm"></div>

                    <div wire:ignore class="max-w-xl mx-auto">
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" id="result" wire:model.live="scannedCode"
                                placeholder="Kode hasil scan akan muncul di sini..." readonly
                                class="text-lg font-mono bg-gray-50 dark:bg-gray-800 border-gray-300 dark:border-gray-700 focus:ring-primary-500 focus:border-primary-500" />
                        </x-filament::input.wrapper>
                    </div>

                    @if($shippingInfo)
                        <div class="max-w-xl mx-auto mt-4 p-4 bg-blue-50 border border-blue-400 rounded shadow">
                            <p><strong>ID:</strong> {{ $shippingInfo['id'] }}</p>
                            <p><strong>Kode:</strong> {{ $shippingInfo['code'] }}</p>
                            <p><strong>Invoice:</strong> {{ $shippingInfo['invoice'] }}</p>
                            <p><strong>Supplier:</strong> {{ $shippingInfo['supplier'] }}</p>
                            <p><strong>Status:</strong> {{ $shippingInfo['status'] }}</p>
                            <p><strong>Produk:</strong></p>
                            <ul>
                                @foreach($shippingInfo['products'] as $product)
                                    <li>{{ $product['name'] }} (Qty: {{ $product['quantity'] }})</li>
                                @endforeach
                            </ul>
                        </div>
                    @elseif($productName)
                        <div class="max-w-xl mx-auto mt-4 p-4 bg-green-50 dark:bg-green-900 border border-green-400 rounded shadow">
                            <p><strong>Nama Produk:</strong> {{ $productName }}</p>
                            <p><strong>Kategori:</strong> {{ $productCategory }}</p>
                            <p><strong>Spesifikasi:</strong> {{ $productSpecification }}</p>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        </div>

        @push('scripts')
            <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>
            <script>
                function onScanSuccess(decodedText) {
                    Livewire.dispatch('codeScanned', { code: decodedText });
                }

                const html5QrcodeScanner = new Html5QrcodeScanner(
                    "reader",
                    {
                        fps: 10,
                        qrbox: { width: 500, height: 200 },
                        rememberLastUsedCamera: true,
                        showTorchButtonIfSupported: true,
                        // aspectRatio: 1.777778,
                        aspectRatio: 3.0,
                        formatsToSupport: [
                            Html5QrcodeSupportedFormats.CODE_128,
                            Html5QrcodeSupportedFormats.EAN_13,
                            Html5QrcodeSupportedFormats.EAN_8,
                            Html5QrcodeSupportedFormats.UPC_A,
                            Html5QrcodeSupportedFormats.UPC_E,
                            Html5QrcodeSupportedFormats.CODE_39,
                            Html5QrcodeSupportedFormats.CODE_93,
                            Html5QrcodeSupportedFormats.CODABAR,
                            Html5QrcodeSupportedFormats.ITF,
                            Html5QrcodeSupportedFormats.QR_CODE
                        ]
                    }
                );
                html5QrcodeScanner.render(onScanSuccess);
            </script>
        @endpush
    </x-filament-panels::page>
