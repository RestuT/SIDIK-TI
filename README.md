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

### 🏢 Modul Administrator (Baru)
* **Manajemen Pengguna**: Kelola akses akun untuk admin, user, dan teknisi secara dinamis.
* **Manajemen Departemen**: Pengaturan divisi/departemen di lingkungan organisasi untuk pemetaan permintaan.

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
    * Buat database baru dengan nama `sidik_ti`.
    * Import file `sidik_ti.sql` (atau `schema_dump.sql` jika ada).
3.  **Konfigurasi Koneksi**:
    * Buka file `config/database.php`.
    * Sesuaikan variabel `$host`, `$user`, `$pass`, dan `$db` sesuai sistem Anda.
4.  **Akses Aplikasi**:
    * Buka browser dan akses `http://localhost/SIDIK-TI`.

---

## 🗄️ Struktur Tabel Penting

* `users`: Menyimpan data user, admin, dan staff.
* `departments`: Menyimpan master data departemen atau divisi organisasi.
* `submissions`: Tabel utama untuk pengadaan, maintenance, dan aju banding.
* `inventory`: Stok barang gudang.
* `procurement_templates`: Master data harga dan spesifikasi produk.
* `budget_config`: Pengaturan batas anggaran tahunan.

---

## 📸 Tampilan Aplikasi

<img width="1887" height="909" alt="image" src="https://github.com/user-attachments/assets/68fc7951-e476-4345-a018-fd3fb2df0d69" />



---

## 🤝 Kontribusi

Aplikasi ini dikembangkan oleh **Restu** sebagai bagian dari proyek sistem bantuan IT di lingkungan pemerintahan daerah.

**SIDIK-TI** - *Efisiensi TI dalam Genggaman.*
