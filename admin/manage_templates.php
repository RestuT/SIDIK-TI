<?php
session_start();
include '../config/database.php';
include '../config/csrf_helper.php';

// Proses Simpan Template Baru & Stok Inisial
if (isset($_POST['save_template'])) {
    require_csrf_token();
    
    $cat = $_POST['category'];
    $name = $_POST['product_name'];
    $spec = $_POST['specification'];
    $price = (float)$_POST['base_price'];
    $initial_stock = (int)$_POST['initial_stock'];
    $satuan = $_POST['satuan'];

    mysqli_begin_transaction($conn);
    try {
        // 1. Simpan ke Master Template
        $stmt = mysqli_prepare($conn, "INSERT INTO procurement_templates (category, product_name, specification, base_price) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssd", $cat, $name, $spec, $price);
        mysqli_stmt_execute($stmt);

        // 2. Inisiasi profil stok ke Inventory jika belum ada
        $stmt_check = mysqli_prepare($conn, "SELECT id FROM inventory WHERE item_name = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $name);
        mysqli_stmt_execute($stmt_check);
        $res = mysqli_stmt_get_result($stmt_check);
        
        if (mysqli_num_rows($res) == 0) {
            $min_stock = 5;
            $stmt_inv = mysqli_prepare($conn, "INSERT INTO inventory (item_name, category, stock, satuan, min_stock, price_reference) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_inv, "ssisid", $name, $cat, $initial_stock, $satuan, $min_stock, $price);
            mysqli_stmt_execute($stmt_inv);
        }
        
        mysqli_commit($conn);
        $pesan_sukses = "Template Baru & Stok Inisial berhasil disimpan.";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $pesan_error = "Error 500: Gagal sinkronisasi data Master Template.";
    }
}

$query_str = "SELECT t.*, i.stock, i.satuan FROM procurement_templates t LEFT JOIN inventory i ON t.product_name = i.item_name ORDER BY t.category ASC";
$templates = mysqli_query($conn, $query_str);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head><body class="bg-slate-100 flex">

    <?php include '../includes/navbar_admin.php'; ?>

<main class="flex-1 p-10 bg-slate-100 min-h-screen">
    <div class="max-w-5xl mx-auto">
        <h2 class="text-3xl font-black text-gray-800 mb-8 uppercase italic">Master <span class="text-blue-600">Template Produk</span></h2>
        
        <?php if(isset($pesan_sukses)): ?>
            <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl font-bold flex items-center gap-3 border border-emerald-200 mb-6">
                <i class="fa-solid fa-check-circle text-xl"></i> <?php echo $pesan_sukses; ?>
            </div>
        <?php elseif(isset($pesan_error)): ?>
            <div class="bg-red-50 text-red-600 p-4 rounded-xl font-bold flex items-center gap-3 border border-red-200 mb-6">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i> <?php echo $pesan_error; ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-8 rounded-[40px] shadow-sm mb-10 border border-blue-100">
            <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Kategori</label>
                        <select name="category" class="w-full p-4 bg-slate-50 border border-gray-100 rounded-2xl outline-none focus:ring-4 focus:ring-blue-100 font-bold text-gray-700">
                            <option value="hardware">Hardware</option>
                            <option value="software">Software</option>
                            <option value="jaringan">Jaringan</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-4 md:col-span-2">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Nama Produk (Merek/Model)</label>
                            <input type="text" name="product_name" required placeholder="Cth: Laptop ASUS Zenbook" class="w-full p-4 bg-slate-50 border border-gray-100 rounded-2xl outline-none focus:ring-4 focus:ring-blue-100 font-bold text-gray-700">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Harga Dasar (Rp)</label>
                            <input type="number" name="base_price" required min="0" placeholder="0" class="w-full p-4 bg-slate-50 border border-gray-100 rounded-2xl outline-none focus:ring-4 focus:ring-blue-100 font-bold text-blue-600">
                        </div>
                    </div>
                </div>

                <div class="col-span-1 border-t border-gray-100 pt-4 mt-2">
                    <label class="block text-[10px] font-black text-blue-400 uppercase mb-2 flex items-center gap-1"><i class="fa-solid fa-box-open"></i> Integrasi Inventory</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <input type="number" name="initial_stock" value="0" min="0" placeholder="Stok..." required class="w-full p-3 bg-blue-50/50 border border-blue-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-200 text-sm font-bold text-blue-700">
                        </div>
                        <div>
                            <input type="text" name="satuan" placeholder="Satuan (Pcs/Unit)" required class="w-full p-3 bg-blue-50/50 border border-blue-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-200 text-sm font-bold text-blue-700">
                        </div>
                    </div>
                </div>

                <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-2 grid grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Spesifikasi Lengkap</label>
                        <textarea name="specification" rows="2" placeholder="Tuliskan CPU, RAM, atau Spek kunci..." class="w-full p-3 bg-slate-50 border border-gray-100 rounded-2xl outline-none focus:ring-4 focus:ring-blue-100 text-sm font-medium text-gray-600 resize-none"></textarea>
                    </div>
                    <div>
                        <button type="submit" name="save_template" class="w-full bg-blue-600 text-white p-4 rounded-2xl font-black uppercase text-xs tracking-widest hover:bg-blue-700 transition active:scale-95 shadow-md shadow-blue-200 h-full flex items-center justify-center gap-2">
                            <i class="fa-solid fa-save"></i> Simpan Data Master
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-[40px] shadow-sm overflow-hidden border border-gray-100">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b">
                    <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="px-8 py-5">Produk & Spek</th>
                        <th class="px-8 py-5">Harga Dasar</th>
                        <th class="px-8 py-5">Stok Saat Ini</th>
                        <th class="px-8 py-5 font-black text-gray-800 text-right">Final User (Total)</th>
                        <th class="px-8 py-5 text-right">Manajemen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php while($t = mysqli_fetch_assoc($templates)): 
                        $ppn = $t['base_price'] * 0.10;
                        $elev = $t['base_price'] * 0.05;
                        $final = $t['base_price'] + $ppn + $elev;
                        
                        // Formatting stock display
                        $stock_val = $t['stock'];
                        $is_low = false;
                        if ($stock_val === null) {
                            $stock_disp = "Belum Diatur";
                        } else {
                            $stock_disp = $stock_val . " " . $t['satuan'];
                            if ($stock_val <= 5) $is_low = true; // Simple logic warning
                        }
                    ?>
                    <tr class="text-sm hover:bg-slate-50 transition border-b border-gray-50">
                        <td class="px-8 py-5">
                            <span class="font-bold text-gray-800 block"><?php echo $t['product_name']; ?></span>
                            <span class="text-[10px] text-gray-400"><?php echo $t['specification']; ?></span>
                        </td>
                        <td class="px-8 py-5 font-medium text-gray-600">Rp <?php echo number_format($t['base_price'], 0, ',', '.'); ?></td>
                        
                        <td class="px-8 py-5 font-black">
                            <?php if($stock_disp == "Belum Diatur"): ?>
                                <span class="text-xs text-slate-400 italic">--</span>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-xs <?php echo $is_low ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>">
                                    <?php echo $stock_disp; ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-8 py-5 text-right">
                            <div class="flex flex-col">
                                <span class="font-black text-orange-600">Rp <?php echo number_format($final, 0, ',', '.'); ?></span>
                                <span class="text-[9px] text-gray-400 uppercase leading-none">(Termasuk PPN & Elevasi)</span>
                            </div>
                        </td>
                        
                        <td class="px-8 py-5 text-right">
                            <a href="../admin/inventory.php" class="bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-600 text-[10px] px-4 py-3 rounded-xl font-bold transition flex items-center justify-end gap-2 w-max ml-auto">
                                <i class="fa-solid fa-box-archive"></i> Kelola Stok
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>