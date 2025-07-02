{{-- filepath: resources/views/filament/resources/shipping-document-resource/pages/view-shipping-qr.blade.php --}}
<x-filament::page>
    <div class="flex flex-col items-center justify-center min-h-[60vh]">
        <h2 class="text-xl font-bold mb-4">QR Code Shipping Document</h2>
        @if($record->qr_code_html_storage)
            <div id="qr-svg" class="mb-4">{!! $record->qr_code_html_storage !!}</div>
            <button
                onclick="downloadQrAsJpg()"
                class="filament-button filament-button--primary"
            >
                Unduh QR Code (JPG)
            </button>
        @else
            <p class="text-gray-500">QR Code belum digenerate.</p>
        @endif
        <a href="{{ url()->previous() }}" class="mt-6 text-blue-600 underline">Kembali</a>
    </div>
    <script>
        function downloadQrAsJpg() {
            const svgElement = document.getElementById('qr-svg').querySelector('svg');
            if (!svgElement) return;

            const svgData = new XMLSerializer().serializeToString(svgElement);
            const svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
            const url = URL.createObjectURL(svgBlob);

            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = "#fff";
                ctx.fillRect(0, 0, canvas.width, canvas.height); // background putih
                ctx.drawImage(img, 0, 0);

                const jpgUrl = canvas.toDataURL('image/jpeg');
                const a = document.createElement('a');
                a.href = jpgUrl;
                a.download = 'shipping-qr-{{ $record->code }}.jpg';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            };
            img.src = url;
        }
    </script>
</x-filament::page>
