<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

// Ambil data Budget untuk ditampilkan di form (Simulasi 2026)
$budget_query = mysqli_query($conn, "SELECT * FROM budget_config WHERE fiscal_year = 2026");
$budget_data = mysqli_fetch_assoc($budget_query);
$sisa_anggaran = $budget_data['total_limit'] - $budget_data['used_amount'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengadaan Barang IT - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans">

<?php include '../includes/navbar_user.php'; ?>

    <main class="max-w-6xl mx-auto px-4 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-wallet text-orange-500"></i> Informasi Anggaran
                    </h3>
                    <p class="text-xs text-gray-500 mb-1">Sisa Pagu Tersedia:</p>
                    <p class="text-xl font-bold text-gray-800">Rp <?php echo number_format($sisa_anggaran, 0, ',', '.'); ?></p>
                    <div class="w-full bg-gray-100 rounded-full h-2 mt-4">
                        <div class="bg-orange-500 h-2 rounded-full" style="width: 65%"></div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-blue-500"></i> Cek Stok Gudang
                    </h3>
                    <div class="space-y-3">
                        <?php 
                        $inv = mysqli_query($conn, "SELECT * FROM inventory LIMIT 3");
                        while($i = mysqli_fetch_assoc($inv)): 
                        ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600"><?php echo $i['item_name']; ?></span>
                            <span class="font-bold <?php echo $i['stock'] > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo $i['stock']; ?> Unit
                            </span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Form Permintaan Barang</h2>
                    
                    <form action="../config/proses_pengadaan.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">ID Pemohon</label>
                                <input type="text" value="<?php echo $_SESSION['user']; ?>" disabled class="w-full p-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Urgensi</label>
                                <select name="urgensi" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none">
                                    <option value="Biasa">Biasa (7-14 Hari)</option>
                                    <option value="Penting">Penting (Mendesak)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Barang & Spesifikasi</label>
                            <input type="text" name="judul" required placeholder="Contoh: Laptop Dell Latitude RAM 16GB" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Estimasi Harga Satuan (Rp)</label>
                            <input type="number" name="estimasi" required placeholder="0" class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi Kebutuhan</label>
                            <textarea name="deskripsi" rows="3" required placeholder="Jelaskan mengapa barang ini dibutuhkan..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Upload Justifikasi (PDF/Gambar)</label>
                            <input type="file" name="lampiran" accept=".pdf, .jpg, .png" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100">
                        </div>

                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 rounded-xl shadow-lg transition transform active:scale-95">
                            Kirim Pengajuan Pengadaan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>