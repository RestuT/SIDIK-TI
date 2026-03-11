<?php
session_start();
include '../config/database.php';
include '../config/csrf_helper.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

// 1. Query JOIN untuk menampilkan data stok + detail dari master template
$query = "SELECT 
            i.*, 
            t.specification, 
            t.base_price as harga_master
          FROM inventory i 
          LEFT JOIN procurement_templates t ON i.item_name = t.product_name 
          ORDER BY i.stock ASC";
$result = mysqli_query($conn, $query);

// 2. Ambil semua data dari Master Template untuk dropdown "Tambah Stok"
$master_templates = mysqli_query($conn, "SELECT * FROM procurement_templates ORDER BY product_name ASC");
$templates_data = [];
while($t = mysqli_fetch_assoc($master_templates)) {
    $templates_data[] = $t;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Inventory - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 flex font-sans">
    <?php include '../includes/navbar_admin.php'; ?>

    <main class="flex-1 p-10">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-3xl font-black text-gray-800 uppercase italic">Stok <span class="text-blue-600">Inventory</span></h2>
                <p class="text-sm text-gray-400">Sinkronisasi stok gudang dengan Master Template Produk</p>
            </div>
            <button onclick="toggleModal('modalTambah')" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-bold text-sm hover:bg-blue-700 transition shadow-lg shadow-blue-100">
                <i class="fa-solid fa-plus mr-2"></i> Update / Tambah Stok
            </button>
        </div>

        <div class="bg-white rounded-[30px] shadow-sm overflow-hidden border border-gray-100">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-8 py-5">Barang & Spesifikasi</th>
                        <th class="px-8 py-5">Kategori</th>
                        <th class="px-8 py-5 text-right">Rincian Biaya (User)</th>
                        <th class="px-8 py-5 text-center">Stok Gudang</th>
                        <th class="px-8 py-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        $harga_dasar = !empty($row['harga_master']) ? $row['harga_master'] : ($row['price_reference'] ?? 0);
                        $total_user = $harga_dasar + ($harga_dasar * 0.10) + ($harga_dasar * 0.05);
                    ?>
                    <tr class="hover:bg-slate-50 transition border-b border-gray-50">
                        <td class="px-8 py-5">
                            <span class="font-bold text-gray-800 block"><?php echo $row['item_name']; ?></span>
                            <p class="text-[10px] text-gray-400 italic"><?php echo $row['specification'] ?? 'Spek belum diatur'; ?></p>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="text-[10px] font-black px-3 py-1 bg-blue-50 text-blue-600 rounded-full uppercase">
                                <?php echo $row['category']; ?>
                            </span>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400">Final Price:</span>
                                <span class="text-sm font-black text-orange-600">Rp <?php echo number_format($total_user, 0, ',', '.'); ?></span>
                            </div>
                        </td>
                        <td class="px-8 py-5 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-black <?php echo $row['stock'] <= $row['min_stock'] ? 'bg-red-100 text-red-600 animate-pulse' : 'bg-green-100 text-green-600'; ?>">
                                <?php echo $row['stock']; ?> <?php echo $row['satuan']; ?>
                            </span>
                        </td>
                        <td class="px-8 py-5 text-right flex justify-end gap-2">
                            <button onclick="bukaModalEdit(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="bg-amber-100 text-amber-600 px-3 py-2 rounded-xl text-xs font-bold hover:bg-amber-500 hover:text-white transition shadow-sm">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <a href="../config/hapus_inventory.php?id=<?php echo $row['id']; ?>" onclick="return confirm(' PERINGATAN! \n\nMenghapus barang ini dari gudang tidak akan menghapus riwayat master template, namun stok fisiknya akan hangus.\n\nLanjutkan penghapusan?')" class="bg-red-100 text-red-600 px-3 py-2 rounded-xl text-xs font-bold hover:bg-red-600 hover:text-white transition shadow-sm">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div id="modalTambah" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
            <div class="bg-white rounded-[40px] max-w-lg w-full p-10 shadow-2xl transition-all scale-95 opacity-0" id="modalContent">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-black text-gray-800 italic uppercase">Update <span class="text-blue-600">Stok Gudang</span></h3>
                    <button onclick="toggleModal('modalTambah')" class="text-gray-400 hover:text-red-500"><i class="fa-solid fa-xmark text-2xl"></i></button>
                </div>

                <form action="../config/proses_inventory_lengkap.php" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1">Pilih Produk (Dari Master Template)</label>
                        <select id="selectTemplate" name="item_name" onchange="autoFillTemplate()" required class="w-full p-3 bg-gray-50 border border-gray-100 rounded-2xl outline-none font-bold text-blue-700">
                            <option value="">-- Pilih Produk Master --</option>
                            <?php foreach($templates_data as $temp): ?>
                                <option value="<?php echo $temp['product_name']; ?>" 
                                        data-cat="<?php echo $temp['category']; ?>"
                                        data-price="<?php echo $temp['base_price']; ?>">
                                    <?php echo $temp['product_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1">Kategori (Auto)</label>
                            <input type="text" id="disp_category" name="category" readonly class="w-full p-3 bg-slate-100 border border-gray-100 rounded-2xl font-bold text-gray-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1">Satuan</label>
                            <input type="text" name="satuan" placeholder="Unit/Pcs" required class="w-full p-3 bg-gray-50 border border-gray-100 rounded-2xl outline-none font-bold">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1">Tambah Jumlah Stok</label>
                            <input type="number" name="stock" required class="w-full p-3 bg-gray-50 border border-gray-100 rounded-2xl outline-none font-bold text-blue-600">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1">Min. Stok Alert</label>
                            <input type="number" name="min_stock" value="5" class="w-full p-3 bg-gray-50 border border-gray-100 rounded-2xl outline-none font-bold text-red-500">
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-2xl">
                        <p class="text-[10px] font-black text-blue-400 uppercase mb-1">Referensi Harga Dasar (Master)</p>
                        <p id="disp_price" class="text-xl font-black text-blue-700 italic">Rp 0</p>
                        <input type="hidden" name="price" id="hidden_price">
                    </div>

                    <button type="submit" name="simpan_inventory" class="w-full bg-blue-600 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-blue-700 transition uppercase tracking-widest text-xs mt-4">
                        <i class="fa-solid fa-box-archive mr-2"></i> Update Stok Gudang
                    </button>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT INVENTORY -->
        <div id="modalEdit" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4 backdrop-blur-sm">
            <div class="bg-white rounded-[40px] max-w-lg w-full p-10 shadow-2xl transition-all scale-95 opacity-0" id="modalEditContent">
                <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                    <h3 class="text-2xl font-black text-gray-800 italic uppercase">Revisi <span class="text-amber-500">Stok Barang</span></h3>
                    <button onclick="tutupModalEdit()" class="text-gray-400 hover:text-red-500 bg-gray-50 h-10 w-10 flex items-center justify-center rounded-xl"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>

                <form action="../config/proses_inventory_lengkap.php" method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="item_id" id="edit_id">
                    
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1">Nama Barang (Read-Only)</label>
                        <input type="text" name="item_name" id="edit_name" readonly class="w-full p-3 bg-gray-100 border border-gray-200 rounded-2xl outline-none font-bold text-gray-500 cursor-not-allowed">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1">Kategori (Read-Only)</label>
                            <input type="text" name="category" id="edit_category" readonly class="w-full p-3 bg-gray-100 border border-gray-200 rounded-2xl font-bold text-gray-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1">Satuan</label>
                            <input type="text" name="satuan" id="edit_satuan" required class="w-full p-3 bg-amber-50 border border-amber-100 rounded-2xl outline-none font-bold text-amber-700">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-amber-500 uppercase mb-1 ml-1">Revisi Sisa Stok Fisik</label>
                            <input type="number" name="stock" id="edit_stock" required class="w-full p-3 bg-amber-50/50 border border-amber-200 rounded-2xl outline-none font-black text-xl text-amber-600 focus:ring-4 focus:ring-amber-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1">Min. Stok Alert</label>
                            <input type="number" name="min_stock" id="edit_min" class="w-full p-3 bg-gray-50 border border-gray-100 rounded-2xl outline-none font-bold text-red-500">
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 mt-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Update Dasar Harga Master (Opsional)</label>
                        <input type="number" name="price" id="edit_price" class="w-full p-3 bg-white border border-gray-200 rounded-xl font-bold text-gray-700">
                    </div>

                    <button type="submit" name="simpan_inventory" class="w-full bg-amber-500 text-white font-black py-4 rounded-2xl shadow-lg hover:bg-amber-600 transition uppercase tracking-widest text-xs mt-6">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Simpan Perubahan Gudang
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Logika Modal
        function toggleModal(id) {
            const modal = document.getElementById(id);
            const content = document.getElementById('modalContent');
            if(modal.classList.contains('hidden')) {
                modal.classList.replace('hidden', 'flex');
                setTimeout(() => {
                    content.classList.replace('scale-95', 'scale-100');
                    content.classList.replace('opacity-0', 'opacity-100');
                }, 10);
            } else {
                content.classList.replace('scale-100', 'scale-95');
                content.classList.replace('opacity-100', 'opacity-0');
                setTimeout(() => modal.classList.replace('flex', 'hidden'), 200);
            }
        }

        // Fungsi Auto-Fill saat memilih produk master
        function autoFillTemplate() {
            const select = document.getElementById('selectTemplate');
            const selectedOption = select.options[select.selectedIndex];
            
            if(selectedOption.value !== "") {
                const cat = selectedOption.getAttribute('data-cat');
                const price = parseFloat(selectedOption.getAttribute('data-price'));

                document.getElementById('disp_category').value = cat;
                document.getElementById('disp_price').innerText = "Rp " + price.toLocaleString('id-ID');
                document.getElementById('hidden_price').value = price;
            } else {
                document.getElementById('disp_category').value = "";
                document.getElementById('disp_price').innerText = "Rp 0";
                document.getElementById('hidden_price').value = "";
            }
        }

        // Logika Eksekusi Edit Modal (Auto population)
        function bukaModalEdit(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_name').value = data.item_name;
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_satuan').value = data.satuan;
            document.getElementById('edit_stock').value = data.stock;
            document.getElementById('edit_min').value = data.min_stock;
            document.getElementById('edit_price').value = data.harga_master ? data.harga_master : (data.price_reference ? data.price_reference : 0);
            
            const modal = document.getElementById('modalEdit');
            const content = document.getElementById('modalEditContent');
            modal.classList.replace('hidden', 'flex');
            setTimeout(() => {
                content.classList.replace('scale-95', 'scale-100');
                content.classList.replace('opacity-0', 'opacity-100');
            }, 10);
        }

        function tutupModalEdit() {
            const modal = document.getElementById('modalEdit');
            const content = document.getElementById('modalEditContent');
            content.classList.replace('scale-100', 'scale-95');
            content.classList.replace('opacity-100', 'opacity-0');
            setTimeout(() => modal.classList.replace('flex', 'hidden'), 200);
        }
    </script>
</body>
</html>