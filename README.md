# SIDIK-TI (Sistem Informasi Digital Infrastruktur & Kelola TI)

**SIDIK-TI** adalah aplikasi helpdesk dan Enterprise Resource Planning (ERP) mini berbasis web yang dirancang untuk memanajemen infrastruktur TI, pengadaan barang, manajemen siklus hidup aset (depresiasi), serta pemeliharaan (maintenance) perangkat secara terpadu. Aplikasi ini baru saja bertransformasi menuju ekosistem infrastruktur **Serverless (Vercel)** dan arsitektur database modern berbasis **NoSQL (Google Cloud Firestore)**.

---

## 🚀 Fitur Utama

### 🛒 Modul Pengadaan (Smart Procurement)
* **Kalkulasi Otomatis**: Perhitungan otomatis biaya margin dan kalkulasi sisa Anggaran/Pagu per Departemen secara *real-time*.
* **Manajemen Pagu DPA**: Sinkronisasi alokasi dana masing-masing departemen secara tersentralisasi.
* **Integrasi Aset Instan**: Barang pengadaan yang berstatus "Selesai" akan secara otomatis direkam, diberi Nomor Seri (SN), dicatat harga belinya, dan didistribusikan ke dasbor "Aset Saya" milik pengguna.

### 🛠️ Modul Maintenance & Solusi Cepat
* **Pelaporan Kendala Terpadu**: User dapat melaporkan kerusakan perangkat TI lengkap dengan foto bukti (dikompresi & divalidasi).
* **Penugasan PIC & Cetak Tiket**: Delegasi otomatis ke teknisi internal, lengkap dengan fasilitas pelacakan QR & cetak tiket fisik untuk verifikasi teknis rujukan lapangan.

### 💼 Manajemen Siklus Hidup Aset (User)
* **Status Kepemilikan Personal**: Menginventarisasikan perangkat kepada pengguna spesifik melalui koleksi *Asset Assignments*.
* **Kalkulasi Depresiasi Real-time**: Menggunakan algoritma penyusutan perangkat keras (4 tahun) hingga titik nilai purna jual/residu, lengkap dengan Market Insight.

### 🛡️ Fitur Tambahan (Admin & Keamanan)
* **Platform Aju Banding**: Fitur pengusulan ulang atas pengajuan tiket yang ditolak oleh Administrator.
* **Database-Encoded Storage**: Arsitektur canggih penyimpanan lampiran yang membungkus gambar bukti menjadi string *Base64* murni ke dalam Firestore, mengecoh batasan *Read-Only* milik lingkungan *Serverless Vercel*.

---

## 🛠️ Teknologi yang Digunakan

* **Core**: PHP 8.x
* **Database**: Google Cloud Firestore (NoSQL Backend)
* **Storage**: Algoritma Storage-Encoded Base64 (Bebas dari limit Firebase Storage berbayar)
* **Frontend**: Tailwind CSS 3.x, Inter & Plus Jakarta Sans
* **Deployment Setup**: Vercel Serverless Architecture

---

## 📋 Ekosistem & Pemasangan Platform

Sejak versi pembaruan *Firestore Migration*, Anda tidak lagi membutuhkan MySQL atau MariaDB. 

### Kebutuhan Dasar
1. **Node.js & Vercel CLI** (Untuk pengetesan Serverless secara lokal)
2. Akun **Google Firebase / Google Cloud Platform**
3. Aplikasi Terminal / PowerShell

### Langkah Instalasi
1.  **Clone / Download** repository aplikasi ini.
2.  **Konfigurasi Google Cloud**:
    * Buat proyek baru di [Firebase Console](https://console.firebase.google.com/).
    * Aktifkan layanan **Firestore Database** (Pilih lokasi server terdekat misal `asia-southeast2`).
    * Pergi ke Project Settings > Service Accounts, lalu *Generate New Private Key*.
    * Simpan *file* JSON kunci rahasia tersebut dengan nama `firebase-auth.json` (Atau ganti namanya jika berbeda) langsung di struktur akar direktori SIDIK-TI.
3.  **Tweak Kredensial Database**:
    * Buka pustaka `api/config/database.php`.
    * Pastikan ia sudah menunjuk ke konfigurasi *path* JSON Anda di atas dan tulis nama `"projectId"` Firebase Anda.
4. **Deploy Ke Vercel**:
    Jalankan perintah ini di terminal root direktori:
    ```powershell
    vercel --prod
    ```
5. Akses tautan publik yang diberikan oleh Vercel.

---

## 🗄️ Arsitektur Koleksi Data (Firestore Schema)

* **`users`**: Entitas akun (Admin, User, Technician).
* **`departments`**: Master struktur divisi & unit pengerahan.
* **`submissions`**: Log antrian Tiket sentral untuk Pengadaan, Maintenance, & Aju Banding.
* **`inventory`**: Master Harga Dasar / *Base Price Reference* barang.
* **`asset_assignments`**: Pendistribusian perangkat fisik final kepada kepemilikan User.
* **`budget_config`**: Alokasi limit plafon pendanaan per departemen selama rentang 1 tahun fiskal.
* **`attachments`**: Brankas Base64 *BLOB* untuk media lampiran cetak tiket terdistribusi (Anti-Vercel Read-Only Lock).

---

## 🤝 Kontribusi

Aplikasi ini dikembangkan oleh **Restu** (dengan optimasi AI arsitektur) sebagai bagian dari proyek sistem bantuan IT di lingkungan pemerintahan daerah guna mencapai era *paperless* dan pendataan transparan.

**SIDIK-TI** - *Efisiensi TI dalam Genggaman Serverless.*
