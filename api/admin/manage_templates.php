<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

$pesan_sukses = null;
$pesan_error = null;

// Proses Simpan Template Baru & Stok Inisial
if (isset($_POST['save_template'])) {
    require_csrf_token();
    
    $cat = $_POST['category'];
    $name = $_POST['product_name'];
    $spec = $_POST['specification'];
    $price = (float)$_POST['base_price'];
    $initial_stock = (int)$_POST['initial_stock'];
    $satuan = $_POST['satuan'];
    $now = date('Y-m-d H:i:s');

    if ($db) {
        try {
            // 1. Simpan ke Master Template
            $db->collection('procurement_templates')->add([
                'category' => $cat, 'product_name' => $name, 'specification' => $spec,
                'base_price' => $price, 'created_at' => $now
            ]);
            // 2. Inisiasi profil stok ke Inventory jika belum ada
            $invQuery = $db->collection('inventory')->where('item_name', '=', $name)->limit(1)->documents();
            if ($invQuery->isEmpty()) {
                $db->collection('inventory')->add([
                    'item_name' => $name, 'category' => $cat, 'stock' => $initial_stock, 'satuan' => $satuan,
                    'min_stock' => 5, 'price_reference' => $price, 'created_at' => $now, 'updated_at' => $now
                ]);
            }
            $pesan_sukses = "Template Baru & Stok Inisial berhasil disimpan.";
        } catch (Exception $e) { $db = null; }
    }
    
    if (!$db && $conn) {
        $cat_e = mysqli_real_escape_string($conn, $cat);
        $name_e = mysqli_real_escape_string($conn, $name);
        $spec_e = mysqli_real_escape_string($conn, $spec);
        $sat_e = mysqli_real_escape_string($conn, $satuan);
        
        $sql1 = "INSERT INTO procurement_templates (category, product_name, specification, base_price, created_at) 
                 VALUES ('$cat_e', '$name_e', '$spec_e', $price, '$now')";
        if (mysqli_query($conn, $sql1)) {
            $checkInv = mysqli_query($conn, "SELECT id FROM inventory WHERE item_name = '$name_e'");
            if (mysqli_num_rows($checkInv) == 0) {
                $sql2 = "INSERT INTO inventory (item_name, category, stock, satuan, min_stock, price_reference, created_at, updated_at) 
                         VALUES ('$name_e', '$cat_e', $initial_stock, '$sat_e', 5, $price, '$now', '$now')";
                mysqli_query($conn, $sql2);
            }
            $pesan_sukses = "Template Baru & Stok Inisial berhasil disimpan (MySQL).";
        } else {
            $pesan_error = "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch Templates and Inventory
$template_data = [];
$inventory_map = [];

if ($db) {
    try {
        $templates_docs = $db->collection('procurement_templates')->orderBy('category', 'ASC')->documents();
        $inventory_docs = $db->collection('inventory')->documents();
        foreach ($inventory_docs as $doc) {
            $inv = $doc->data(); $inventory_map[$inv['item_name']] = $inv;
        }
        foreach ($templates_docs as $doc) {
            $t = $doc->data(); $name = $t['product_name'];
            $t['stock'] = $inventory_map[$name]['stock'] ?? null;
            $t['satuan'] = $inventory_map[$name]['satuan'] ?? '';
            $template_data[] = $t;
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $res_inv = mysqli_query($conn, "SELECT * FROM inventory");
    if ($res_inv) { while ($row = mysqli_fetch_assoc($res_inv)) { $inventory_map[$row['item_name']] = $row; } }
    $res_temp = mysqli_query($conn, "SELECT * FROM procurement_templates ORDER BY category ASC");
    if ($res_temp) {
        while ($row = mysqli_fetch_assoc($res_temp)) {
            $name = $row['product_name'];
            $row['stock'] = $inventory_map[$name]['stock'] ?? null;
            $row['satuan'] = $inventory_map[$name]['satuan'] ?? '';
            $template_data[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Master Template Management';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen">
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>
    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-30">
            <div>
                <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Master <span class="text-primary italic md:text-3xl">Product</span> Template</h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest mt-1">Konfigurasi Standardisasi Aset TI</p>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6 md:space-y-8">
            <?php if($pesan_sukses): ?>
                <div class="bg-emerald-100 text-emerald-700 p-4 rounded-2xl flex items-center gap-3 border border-emerald-200 font-bold uppercase tracking-widest text-xs">
                    <span class="material-symbols-outlined fill-1">check_circle</span> <?php echo $pesan_sukses; ?>
                </div>
            <?php elseif($pesan_error): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded-2xl flex items-center gap-3 border border-red-200 font-bold uppercase tracking-widest text-xs">
                    <span class="material-symbols-outlined fill-1">report</span> <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <section class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-2xl relative overflow-hidden group">
                <h2 class="flex items-center gap-3 font-headline text-lg font-black text-on-surface uppercase tracking-tight mb-8">
                    <span class="material-symbols-outlined text-primary">add_circle</span> Registrasi Master Template Baru
                </h2>
                <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="lg:col-span-3 space-y-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Kategori Utama</label>
                            <select name="category" class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface">
                                <option value="hardware">Hardware / Perangkat</option>
                                <option value="software">Software / Lisensi</option>
                                <option value="jaringan">Infrastruktur Jaringan</option>
                            </select>
                        </div>
                        <div class="p-6 bg-primary-fixed/10 border border-primary-fixed/30 rounded-3xl">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="material-symbols-outlined text-primary text-sm">inventory_2</span>
                                <label class="block text-[10px] font-black text-primary uppercase tracking-[0.2em]">Inisiasi Inventory</label>
                            </div>
                            <div class="space-y-4">
                                <input type="number" name="initial_stock" value="0" min="0" placeholder="Qty..." required class="block w-full px-4 py-3 bg-white border border-primary-fixed/50 rounded-xl font-black text-primary text-sm">
                                <input type="text" name="satuan" placeholder="Satuan (Unit/Pcs)" required class="block w-full px-4 py-3 bg-white border border-primary-fixed/50 rounded-xl font-bold text-on-surface text-xs">
                            </div>
                        </div>
                    </div>
                    <div class="lg:col-span-9 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Produk (Merek/Model)</label>
                                <input type="text" name="product_name" required placeholder="Cth: Laptop ASUS" class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-sm">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Harga Dasar (Rp)</label>
                                <input type="number" name="base_price" required min="0" placeholder="0" class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl font-black text-primary text-sm">
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Spesifikasi</label>
                            <textarea name="specification" rows="3" placeholder="Detail teknis..." class="block w-full px-5 py-4 bg-surface-container-low border-0 rounded-3xl font-bold text-sm min-h-[120px]"></textarea>
                        </div>
                        <div class="flex justify-end pt-2">
                            <button type="submit" name="save_template" class="px-10 py-5 bg-primary text-white font-headline font-black rounded-2xl shadow-xl uppercase tracking-widest text-xs">Simpan Data Master</button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="bg-white rounded-[2.5rem] border border-outline-variant/10 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-outline-variant/5 mb-2">
                    <h2 class="font-headline text-xl font-bold text-on-surface italic uppercase tracking-tighter">Database <span class="text-primary italic">Product Templates</span></h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/30 border-b border-outline-variant/10">
                                <th class="px-8 py-5">Detail Produk</th>
                                <th class="px-8 py-5">Harga Dasar</th>
                                <th class="px-8 py-5 text-primary">Final User</th>
                                <th class="px-8 py-5 text-center">Gudang</th>
                                <th class="px-8 py-5 text-right w-44">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/5">
                            <?php foreach($template_data as $t): 
                                $ppn = $t['base_price'] * 0.11; $elev = $t['base_price'] * 0.10; // Assuming 11% tax and 10% margin
                                $final = $t['base_price'] + $ppn + $elev;
                                $stock_val = $t['stock'];
                            ?>
                            <tr class="group hover:bg-surface-variant/5 transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-[9px] font-black text-indigo-700 italic uppercase"><?php echo $t['category']; ?></span>
                                        <span class="font-headline font-bold text-on-surface text-sm uppercase"><?php echo $t['product_name']; ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-sm font-black text-on-surface-variant">Rp <?php echo number_format($t['base_price'], 0, ',', '.'); ?></td>
                                <td class="px-8 py-6 font-headline font-black text-orange-600 text-sm italic">Rp <?php echo number_format($final, 0, ',', '.'); ?></td>
                                <td class="px-8 py-6 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black border <?php echo ($stock_val !== null && $stock_val <= 5) ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'; ?>">
                                        <?php echo ($stock_val === null) ? 'Unmapped' : $stock_val . " " . $t['satuan']; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <a href="inventory.php" class="bg-surface-container px-4 py-2.5 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-primary hover:text-white transition-all">Gudang</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
