<script>
    document.addEventListener('livewire:initialized', () => {
        // Mendengarkan event 'download-barcode-jpg' dari Livewire
        Livewire.on('download-barcode-jpg', ({ base64Data, fileName }) => {
            try {
                // Buat blob dari data base64
                const byteCharacters = atob(base64Data);
                const byteNumbers = new Array(byteCharacters.length);
                for (let i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }
                const byteArray = new Uint8Array(byteNumbers);
                const blob = new Blob([byteArray], { type: 'image/jpeg' });

                // Buat URL objek untuk blob
                const url = URL.createObjectURL(blob);

                // Buat elemen <a> sementara untuk memicu unduhan
                const a = document.createElement('a');
                a.href = url;
                a.download = fileName; // Atur nama file unduhan

                // Tambahkan elemen ke DOM (tidak terlihat oleh pengguna)
                document.body.appendChild(a);

                // Klik elemen <a> secara terprogram untuk memicu unduhan
                a.click();

                // Hapus URL objek dan elemen <a> setelah unduhan dimulai
                URL.revokeObjectURL(url);
                document.body.removeChild(a);

            } catch (error) {
                console.error('Error saat mengunduh barcode di sisi klien:', error);
                // Anda juga bisa mengirim notifikasi kembali ke Livewire jika perlu
                // Livewire.dispatch('barcode-download-failed', { message: error.message });
            }
        });
    });
</script>
