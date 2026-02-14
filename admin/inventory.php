<?php
session_start();
include '../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Ambil data stok
$query = "SELECT * FROM inventory ORDER BY item_name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Inventory - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 flex">

<?php include '../includes/navbar_admin.php'; ?>

    <main class="flex-1 p-10">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase">Manajemen Stok Aset</h2>
            <button onclick="document.getElementById('modalTambah').classList.remove('hidden')" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i> Tambah Item
            </button>
        </div>

        <div class="bg-white rounded-[30px] shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-8 py-5 text-xs font-black text-slate-400 uppercase tracking-widest">Nama Barang</th>
                        <th class="px-8 py-5 text-xs font-black text-slate-400 uppercase tracking-widest text-center">Stok Tersedia</th>
                        <th class="px-8 py-5 text-xs font-black text-slate-400 uppercase tracking-widest text-center">Satuan</th>
                        <th class="px-8 py-5 text-xs font-black text-slate-400 uppercase tracking-widest text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-8 py-5 font-bold text-slate-700"><?php echo $row['item_name']; ?></td>
                        <td class="px-8 py-5 text-center">
                            <span class="px-4 py-1 rounded-full font-black text-lg <?php echo $row['stock'] <= 5 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>">
                                <?php echo $row['stock']; ?>
                            </span>
                        </td>
                        <td class="px-8 py-5 text-center text-slate-400 font-medium"><?php echo $row['satuan']; ?></td>
                       <td class="px-8 py-5 text-center">
    <div class="flex items-center justify-center gap-4">
        <form action="../config/update_inventory.php" method="POST" class="flex items-center gap-2">
            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
            <input type="number" name="change" value="1" class="w-12 p-1 border rounded text-center text-sm font-bold">
            <button type="submit" name="action" value="add" class="text-green-500 hover:text-green-700"><i class="fa-solid fa-square-plus text-xl"></i></button>
            <button type="submit" name="action" value="sub" class="text-amber-500 hover:text-amber-700"><i class="fa-solid fa-square-minus text-xl"></i></button>
        </form>

        <div class="h-8 w-px bg-slate-200"></div>

        <a href="../config/hapus_inventory.php?id=<?php echo $row['id']; ?>" 
           onclick="return confirm('Apakah Anda yakin ingin menghapus produk \'<?php echo $row['item_name']; ?>\' secara permanen?')"
           class="text-red-500 hover:bg-red-50 p-2 rounded-xl transition-all">
            <i class="fa-solid fa-trash-can"></i>
        </a>
    </div>
</td>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalTambah" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-[30px] shadow-2xl p-8 border border-slate-100">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-black text-slate-800 uppercase tracking-tighter">Tambah Aset Baru</h3>
            <button onclick="document.getElementById('modalTambah').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        
        <form action="../config/tambah_inventory.php" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Nama Barang</label>
                <input type="text" name="item_name" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 outline-none font-bold" placeholder="Contoh: Monitor LG 24 Inch">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Jumlah Stok</label>
                    <input type="number" name="stock" required min="0" class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 outline-none font-bold" placeholder="0">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Satuan</label>
                    <input type="text" name="satuan" required class="w-full px-5 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 outline-none font-bold" placeholder="Unit/Pcs">
                </div>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl shadow-xl transition-all active:scale-95 uppercase tracking-widest mt-4">
                Simpan ke Gudang
            </button>
        </form>
    </div>
</div>

</body>
</html>