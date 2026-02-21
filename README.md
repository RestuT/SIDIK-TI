# SIDIK-TI (Sistem Informasi Digital Infrastruktur & Kelola TI)

**SIDIK-TI** adalah aplikasi helpdesk berbasis web yang dirancang khusus untuk memanajemen infrastruktur TI, pengadaan barang, serta pemeliharaan (maintenance) perangkat secara terpadu. Aplikasi ini mengintegrasikan data inventory dengan proses penganggaran untuk memastikan transparansi dan efisiensi operasional.



## 🚀 Fitur Utama

### 🛠️ Modul Maintenance (Baru)
* **Pelaporan Kendala**: User dapat melaporkan kerusakan perangkat TI lengkap dengan foto dokumentasi.
* **Manajemen PIC**: Admin dapat menugaskan teknisi spesifik untuk menangani laporan.
* **Cetak Tiket MNT**: Bukti laporan fisik untuk verifikasi lapangan.

### 🛒 Modul Pengadaan (Smart Procurement)
* **Kalkulasi Otomatis**: Perhitungan otomatis Harga Dasar + PPN 10% + Kenaikan Elevasi 5%.
* **Integrasi Anggaran**: Validasi sisa anggaran DPA Dinas tahun fiskal berjalan.
* **Template Produk**: Admin dapat mengelola master data produk dan harga referensi.

### 📦 Modul Inventory
* **Stok Kritis Alert**: Notifikasi visual (pulse animation) jika stok di bawah limit.
* **Direct-to-Procurement**: Tombol restock otomatis yang mengisi data form pengadaan berdasarkan data inventory.

### ⚖️ Fitur Aju Banding
* User dapat mengajukan banding atas pengajuan yang **Ditolak** dengan memberikan alasan perbaikan atau data tambahan.

---

## 🛠️ Teknologi yang Digunakan

* **Core**: PHP 8.x
* **Database**: MySQL / MariaDB
* **Frontend**: Tailwind CSS 3.x
* **Icons**: FontAwesome 6 Free
* **Library**: MySQLi (Procedural/Object-Oriented)

---

## 📋 Prasyarat Instalasi

1.  **Web Server**: XAMPP / Laragon (PHP >= 8.0)
2.  **Database Server**: MySQL
3.  **Browser**: Google Chrome / Microsoft Edge (Terbaru)

---

## ⚙️ Cara Instalasi

1.  **Clone atau Download** repository ini ke folder `htdocs` Anda.
2.  **Import Database**:
    * Buka `phpMyAdmin`.
    * Buat database baru dengan nama `it_helpdesk_db`.
    * Import file `it_helpdesk_db.sql` (jika ada) atau jalankan query struktur tabel yang tersedia.
3.  **Konfigurasi Koneksi**:
    * Buka file `config/database.php`.
    * Sesuaikan `host`, `user`, `password`, dan `database_name`.
4.  **Akses Aplikasi**:
    * Buka browser dan akses `http://localhost/SIDIK-TI`.

---

## 🗄️ Struktur Tabel Penting

* `users`: Menyimpan data user, admin, dan staff.
* `submissions`: Tabel utama untuk pengadaan, maintenance, dan aju banding.
* `inventory`: Stok barang gudang.
* `procurement_templates`: Master data harga dan spesifikasi produk.
* `budget_config`: Pengaturan batas anggaran tahunan.

---

## 📸 Tampilan Aplikasi

<img width="1920" height="1080" alt="image" src="https://github.com/user-attachments/assets/027f45c2-34da-4f03-b1a0-9450d8300ab3" />


---

## 🤝 Kontribusi

Aplikasi ini dikembangkan oleh **Restu** sebagai bagian dari proyek sistem bantuan IT di lingkungan pemerintahan daerah.

**SIDIK-TI** - *Efisiensi TI dalam Genggaman.*
