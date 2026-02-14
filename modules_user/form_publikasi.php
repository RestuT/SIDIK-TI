<?php
session_start();
// Proteksi: Pastikan user_id tersedia di session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Publikasi - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans">

<?php include '../includes/navbar_user.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Form Pengajuan Publikasi</h2>
            
            <form action="../config/proses_publikasi.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Pemohon (Otomatis)</label>
                        <input type="text" value="<?php echo $_SESSION['user']; ?>" disabled class="w-full p-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Departemen</label>
                        <input type="text" name="departemen" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Judul Publikasi</label>
                    <input type="text" name="judul" required placeholder="Judul konten/agenda" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Jenis Layanan</label>
                    <select name="layanan" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                        <option value="Publikasi Website">Publikasi Website Pemerintah</option>
                        <option value="Administrasi">Administrasi Surat Keluar IT</option>
                        <option value="Dokumentasi">Dokumentasi Kegiatan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi Detail</label>
                    <textarea name="deskripsi" rows="4" required placeholder="Jelaskan detail pengajuan Anda..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Lampiran Dokumen</label>
                    <div class="relative group">
                        <input type="file" name="lampiran" id="file-upload" accept=".pdf, .docx, .jpg, .png" required 
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="updateFileName()">
                        <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center bg-gray-50 group-hover:border-blue-400 transition">
                            <i id="upload-icon" class="fa-solid fa-cloud-arrow-up text-gray-400 text-3xl mb-2"></i>
                            <p id="file-label" class="text-sm text-gray-600 font-medium">Klik untuk unggah (PDF/DOCX/JPG)</p>
                            <p id="file-name-display" class="text-sm font-bold text-blue-700 mt-2 hidden"></p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg transition transform active:scale-95">
                    Kirim Pengajuan
                </button>
            </form>
        </div>
    </main>

    <script src="../includes/input.js"></script>
</body>
</html>