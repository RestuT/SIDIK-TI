document.addEventListener('DOMContentLoaded', function() {
    const kategoriSelect = document.getElementById('kategoriBarang');
    const templateSelect = document.getElementById('productTemplate');
    const optgroups = templateSelect.getElementsByTagName('optgroup');

    // 1. Logika Filter: Tampilkan template berdasarkan kategori
    kategoriSelect.addEventListener('change', function() {
        console.log("Kategori dipilih: " + this.value);
        const selectedKategori = this.value;
        
        if (selectedKategori === "") {
            templateSelect.disabled = true;
            templateSelect.value = "";
        } else {
            templateSelect.disabled = false;
            templateSelect.innerHTML = '<option value="">-- Pilih Template Barang --</option>'; // Reset

            // Hanya tambahkan optgroup yang sesuai dengan kategori
            for (let i = 0; i < optgroups.length; i++) {
                if (optgroups[i].getAttribute('data-category') === selectedKategori) {
                    templateSelect.appendChild(optgroups[i].cloneNode(true));
                }
            }
        }
    });

    // 2. Logika Kalkulasi Otomatis (Sama seperti sebelumnya)
    templateSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        if (selected && selected.value !== "") {
            const basePrice = parseFloat(selected.getAttribute('data-price'));
            const spec = selected.getAttribute('data-spec');
            
            const tax = basePrice * 0.10; // PPN 10%
            const elevation = basePrice * 0.05; // Elevasi 5%
            const totalPrice = basePrice + tax + elevation;

            // Mengisi field form secara otomatis
            document.getElementsByName('judul')[0].value = spec;
            document.getElementsByName('estimasi')[0].value = Math.round(totalPrice);
            document.getElementsByName('deskripsi')[0].value = 
                `Kategori: ${kategoriSelect.options[kategoriSelect.selectedIndex].text}\n` +
                `Spesifikasi: ${spec}\n` +
                `-----------------------------------\n` +
                `Harga Dasar: Rp ${basePrice.toLocaleString('id-ID')}\n` +
                `PPN (10%): Rp ${tax.toLocaleString('id-ID')}\n` +
                `Elevasi Harga (5%): Rp ${elevation.toLocaleString('id-ID')}`;
        }
    });
});