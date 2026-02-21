<?php
session_start();
include '../config/database.php'; //

// Proteksi halaman: Memastikan hanya user yang sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php"); //
    exit();
}

// Ambil data user untuk identitas pengusul
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT full_name, jabatan, department FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Maintenance TI - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans">

<?php include '../includes/navbar_user.php'; // ?>

    <main class="max-w-4xl mx-auto px-4 py-12">
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Form Maintenance & Perbaikan</h2>
                    <p class="text-sm text-gray-500">Laporkan kendala perangkat TI Anda untuk segera ditangani tim teknis.</p>
                </div>
            </div>
            
            <form action="../config/proses_maintenance.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Nama Pemohon</label>
                        <input type="text" value="<?php echo $user_data['full_name']; ?>" disabled class="w-full p-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-500 font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Departemen</label>
                        <input type="text" value="<?php echo $user_data['department']; ?>" disabled class="w-full p-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-500 font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Jenis Barang / Perangkat</label>
                        <select name="layanan" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none font-medium">
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Laptop/PC">Laptop / Komputer</option>
                            <option value="Printer/Scanner">Printer / Scanner</option>
                            <option value="Jaringan/WiFi">Perangkat Jaringan (WiFi/Switch)</option>
                            <option value="Server/Aplikasi">Server / Software Aplikasi</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Nama & Merk Barang</label>
                        <input type="text" name="judul" required placeholder="Contoh: Laptop Dell Latitude / Printer Epson L3110" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Keluhan / Detail Kendala</label>
                    <textarea name="deskripsi" rows="4" required placeholder="Jelaskan secara detail kerusakan atau kendala yang dialami..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Dokumentasi Barang (Foto Kerusakan)</label>
                    <div class="relative group">
                        <input type="file" name="lampiran" id="file-upload" accept=".jpg, .jpeg, .png, .pdf" required 
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="updateFileName()">
                        <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-2xl p-8 text-center bg-gray-50 group-hover:border-emerald-400 transition">
                            <i class="fa-solid fa-camera text-gray-400 text-3xl mb-2"></i>
                            <p id="file-label" class="text-sm text-gray-600 font-medium">Klik atau drag foto barang untuk dokumentasi</p>
                            <p id="file-name-display" class="text-sm font-bold text-emerald-700 mt-2 hidden"></p>
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2">*Format yang diizinkan: JPG, PNG, PDF (Maks 2MB)</p>
                </div>

                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 rounded-2xl shadow-lg transition transform active:scale-95 flex items-center justify-center gap-2 uppercase tracking-widest">
                    <i class="fa-solid fa-paper-plane"></i> Kirim Laporan Maintenance
                </button>
            </form>
        </div>
    </main>

    <script>
        function updateFileName() {
            const input = document.getElementById('file-upload');
            const display = document.getElementById('file-name-display');
            const label = document.getElementById('file-label');
            const dropzone = document.getElementById('dropzone');

            if (input.files.length > 0) {
                display.innerText = "File terpilih: " + input.files[0].name;
                display.classList.remove('hidden');
                label.classList.add('hidden');
                dropzone.classList.add('border-emerald-500', 'bg-emerald-50');
            }
        }
    </script>
    <?php include '../includes/footer.php'; // ?>
</body>
</html>