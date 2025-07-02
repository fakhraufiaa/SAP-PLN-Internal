<script>
    document.addEventListener('livewire:initialized', () => {
        // Mendengarkan event 'display-shipping-qr' dari Livewire
        Livewire.on('display-shipping-qr', ({ qrSvgHtml }) => {
            // Dapatkan elemen placeholder dari form.
            // Kita akan mencari elemen dengan atribut 'data-field-name="qr_code_display"'
            // dan kemudian mencari bagian untuk kontennya (misalnya, .filament-forms-placeholder-label).
            const qrDisplayElement = document.querySelector('[data-field-name="qr_code_display"] .filament-forms-placeholder-label');
            if (qrDisplayElement) {
                qrDisplayElement.innerHTML = qrSvgHtml;
            } else {
                console.warn('Elemen tampilan QR code tidak ditemukan. Pastikan ada Forms\\Components\\Placeholder::make(\'qr_code_display\').');
            }
        });

        // Bagian download QR Code akan ditambahkan di sini nanti

        Livewire.dispatch('codeScanned', { code: decodedText, type: scannerType });
    });
</script>
