<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proses Simpan Template Baru & Stok Inisial
if (isset($_POST['save_template'])) {
    require_csrf_token();
    
    $cat = $_POST['category'];
    $name = $_POST['product_name'];
    $spec = $_POST['specification'];
    $price = (float)$_POST['base_price'];
    $initial_stock = (int)$_POST['initial_stock'];
    $satuan = $_POST['satuan'];

    try {
        // 1. Simpan ke Master Template
        $db->collection('procurement_templates')->add([
            'category' => $cat,
            'product_name' => $name,
            'specification' => $spec,
            'base_price' => $price,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // 2. Inisiasi profil stok ke Inventory jika belum ada
        $invQuery = $db->collection('inventory')
            ->where('item_name', '=', $name)
            ->limit(1)
            ->documents();
        
        if ($invQuery->isEmpty()) {
            $min_stock = 5;
            $db->collection('inventory')->add([
                'item_name' => $name,
                'category' => $cat,
                'stock' => $initial_stock,
                'satuan' => $satuan,
                'min_stock' => $min_stock,
                'price_reference' => $price,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        $pesan_sukses = "Template Baru & Stok Inisial berhasil disimpan.";
    } catch (Exception $e) {
        $pesan_error = "Error 500: Gagal sinkronisasi data Master Template. " . $e->getMessage();
    }
}

// Fetch Templates and Inventory for joining
$templates_docs = $db->collection('procurement_templates')->orderBy('category', 'ASC')->documents();
$inventory_docs = $db->collection('inventory')->documents();

// Map inventory by item_name for easy lookup
$inventory_map = [];
foreach ($inventory_docs as $doc) {
    $inv = $doc->data();
    $inventory_map[$inv['item_name']] = $inv;
}

$template_data = [];
foreach ($templates_docs as $doc) {
    $t = $doc->data();
    $name = $t['product_name'];
    $t['stock'] = $inventory_map[$name]['stock'] ?? null;
    $t['satuan'] = $inventory_map[$name]['satuan'] ?? '';
    $template_data[] = $t;
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Master Template Management</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary-fixed-dim": "#c3c0ff",
                        "on-error": "#ffffff",
                        "on-error-container": "#93000a",
                        "on-secondary-container": "#fefcff",
                        "on-tertiary-container": "#67f4b7",
                        "inverse-surface": "#2d3133",
                        "surface-variant": "#e0e3e5",
                        "primary-fixed": "#e2dfff",
                        "tertiary": "#005338",
                        "secondary": "#0051d5",
                        "on-surface": "#191c1e",
                        "background": "#f7f9fb",
                        "on-primary-container": "#dad7ff",
                        "tertiary-fixed-dim": "#4edea3",
                        "surface-tint": "#4d44e3",
                        "on-tertiary": "#ffffff",
                        "secondary-fixed-dim": "#b4c5ff",
                        "secondary-fixed": "#dbe1ff",
                        "surface-container-low": "#f2f4f6",
                        "on-surface-variant": "#464555",
                        "on-secondary": "#ffffff",
                        "surface": "#f7f9fb",
                        "error-container": "#ffdad6",
                        "error": "#ba1a1a",
                        "surface-container-high": "#e6e8ea",
                        "on-tertiary-fixed-variant": "#005236",
                        "surface-container-highest": "#e0e3e5",
                        "on-primary-fixed-variant": "#3323cc",
                        "on-primary": "#ffffff",
                        "primary-container": "#4f46e5",
                        "outline-variant": "#c7c4d8",
                        "on-primary-fixed": "#0f0069",
                        "inverse-on-surface": "#eff1f3",
                        "tertiary-fixed": "#6ffbbe",
                        "on-secondary-fixed-variant": "#003ea8",
                        "primary": "#3525cd",
                        "surface-bright": "#f7f9fb",
                        "secondary-container": "#316bf3",
                        "on-background": "#191c1e",
                        "surface-container-lowest": "#ffffff",
                        "tertiary-container": "#006e4b",
                        "surface-dim": "#d8dadc",
                        "on-secondary-fixed": "#00174b",
                        "surface-container": "#eceef0",
                        "inverse-primary": "#c3c0ff",
                        "outline": "#777587",
                        "on-tertiary-fixed": "#002113"
                    },
                    fontFamily: {
                        "headline": ["Plus Jakarta Sans"],
                        "body": ["Inter"],
                        "label": ["Inter"]
                    },
                    borderRadius: {"DEFAULT": "1rem", "lg": "2rem", "xl": "3rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .fill-1 { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen flex flex-col lg:flex-row">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 pt-14 lg:pt-0">
        <!-- Header Bar -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-20">
            <div>
                <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Master <span class="text-primary italic">Product</span> Template</h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest mt-1">Konfigurasi Standardisasi Aset TI</p>
            </div>
            <div class="flex items-center gap-4">
                <button class="w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-outline transition-all">
                    <span class="material-symbols-outlined">help</span>
                </button>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6 md:space-y-8">
            <!-- Messages -->
            <?php if(isset($pesan_sukses)): ?>
                <div class="bg-emerald-100 text-emerald-700 p-4 rounded-2xl flex items-center gap-3 border border-emerald-200 animate-in fade-in slide-in-from-top-2 duration-300 font-bold uppercase tracking-widest text-xs">
                    <span class="material-symbols-outlined fill-1">check_circle</span>
                    <?php echo $pesan_sukses; ?>
                </div>
            <?php elseif(isset($pesan_error)): ?>
                <div class="bg-error-container text-on-error-container p-4 rounded-2xl flex items-center gap-3 border border-error/20 animate-in fade-in slide-in-from-top-2 duration-300 font-bold uppercase tracking-widest text-xs">
                    <span class="material-symbols-outlined fill-1">report</span>
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <!-- Add Template Form Panel -->
            <section class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-2xl shadow-indigo-900/5 relative overflow-hidden group">
                <div class="absolute top-0 left-0 w-1.5 h-full bg-primary/20 group-hover:bg-primary transition-all duration-500"></div>
                
                <h2 class="flex items-center gap-3 font-headline text-lg font-black text-on-surface uppercase tracking-tight mb-8">
                    <span class="material-symbols-outlined text-primary">add_circle</span>
                    Registrasi Master Template Baru
                </h2>

                <form action="" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="lg:col-span-3 space-y-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Kategori Utama</label>
                            <div class="relative group">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary transition-colors text-lg">category</span>
                                <select name="category" class="block w-full pl-12 pr-10 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 appearance-none transition-all text-sm">
                                    <option value="hardware">Hardware / Perangkat</option>
                                    <option value="software">Software / Lisensi</option>
                                    <option value="jaringan">Infrastruktur Jaringan</option>
                                </select>
                                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                            </div>
                        </div>
                        <div class="p-6 bg-primary-fixed/10 border border-primary-fixed/30 rounded-3xl group/box">
                            <div class="flex items-center gap-2 mb-4">
                                <span class="material-symbols-outlined text-primary text-sm">inventory_2</span>
                                <label class="block text-[10px] font-black text-primary uppercase tracking-[0.2em]">Inisiasi Inventory</label>
                            </div>
                            <div class="space-y-4">
                                <div class="relative">
                                    <input type="number" name="initial_stock" value="0" min="0" placeholder="Qty Inisial..." required 
                                        class="block w-full pl-4 pr-12 py-3 bg-white border border-primary-fixed/50 rounded-xl font-black text-primary outline-none focus:ring-2 focus:ring-primary/20 text-sm transition-all">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-primary/50 uppercase">STOK</span>
                                </div>
                                <div class="relative">
                                    <input type="text" name="satuan" placeholder="Satuan (Unit/Pcs)" required 
                                        class="block w-full px-4 py-3 bg-white border border-primary-fixed/50 rounded-xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 text-xs transition-all">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-9 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Identitas Produk (Merek/Model)</label>
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary text-lg">label</span>
                                    <input type="text" name="product_name" required placeholder="Cth: Laptop ASUS Zenbook Flip" 
                                        class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Referensi Harga Dasar (Rp)</label>
                                <div class="relative group/price">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-black text-primary group-focus-within/price:scale-110 transition-transform">Rp</span>
                                    <input type="number" name="base_price" required min="0" placeholder="0" 
                                        class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl font-black text-primary outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Spesifikasi Lengkap / Keterangan</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-5 text-primary text-lg">description</span>
                                <textarea name="specification" rows="3" placeholder="Tuliskan detail teknis: CPU, RAM, Storage, atau Masa Berlaku Lisensi..." 
                                    class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-3xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all text-sm min-h-[120px] resize-none"></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" name="save_template" class="px-10 py-5 bg-gradient-to-br from-indigo-600 to-indigo-500 text-white font-headline font-black rounded-2xl shadow-xl shadow-indigo-900/10 hover:shadow-indigo-900/30 hover:-translate-y-1 transition-all active:scale-95 flex items-center gap-3 uppercase tracking-widest text-xs">
                                <span class="material-symbols-outlined text-lg fill-1">save</span>
                                Simpan Data Master
                            </button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- Table Section -->
            <section class="bg-white rounded-[2.5rem] border border-outline-variant/10 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-outline-variant/5 mb-2">
                    <h2 class="font-headline text-xl font-bold text-on-surface italic uppercase tracking-tighter">Database <span class="text-primary italic">Product Templates</span></h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/30 border-b border-outline-variant/10">
                                <th class="px-8 py-5">Detail Produk Master</th>
                                <th class="px-8 py-5">Harga Dasar</th>
                                <th class="px-8 py-5 font-black text-primary">Estimasi Final User</th>
                                <th class="px-8 py-5 text-center">Status Inventory</th>
                                <th class="px-8 py-5 text-right">Manajemen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/5">
                            <?php foreach($template_data as $t): 
                                $ppn = $t['base_price'] * 0.10;
                                $elev = $t['base_price'] * 0.05;
                                $final = $t['base_price'] + $ppn + $elev;
                                
                                $stock_val = $t['stock'];
                                $stock_disp = ($stock_val === null) ? "Gudang Belum Diatur" : $stock_val . " " . $t['satuan'];
                                $is_low = ($stock_val !== null && $stock_val <= 5);
                            ?>
                            <tr class="group hover:bg-surface-variant/5 transition-all">
                                <td class="px-8 py-6 max-w-sm">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-[9px] font-black px-2 py-0.5 rounded-full uppercase tracking-tighter <?php echo $t['category'] == 'hardware' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700'; ?>">
                                                <?php echo $t['category']; ?>
                                            </span>
                                            <span class="font-headline font-bold text-on-surface group-hover:text-primary transition-colors leading-none"><?php echo $t['product_name']; ?></span>
                                        </div>
                                        <p class="text-[10px] text-outline font-medium truncate italic leading-tight"><?php echo htmlspecialchars($t['specification'] ?? ''); ?></p>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-sm font-black text-on-surface-variant">Rp <?php echo number_format($t['base_price'], 0, ',', '.'); ?></span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="font-headline font-black text-orange-600 text-base leading-none">Rp <?php echo number_format($final, 0, ',', '.'); ?></span>
                                        <span class="text-[9px] text-outline font-bold mt-1 uppercase tracking-tighter leading-none">Incl. PPN + Biaya Admin</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <?php if($stock_val === null): ?>
                                        <span class="text-[11px] font-black text-outline italic uppercase opacity-40 leading-none">Unmapped</span>
                                    <?php else: ?>
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[10px] font-black border <?php echo $is_low ? 'bg-red-50 text-red-700 border-red-100 animate-pulse' : 'bg-emerald-50 text-emerald-700 border-emerald-100'; ?>">
                                            <span class="w-1.5 h-1.5 rounded-full <?php echo $is_low ? 'bg-red-500' : 'bg-emerald-500'; ?>"></span>
                                            <?php echo strtoupper($stock_disp); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <a href="../admin/inventory.php" class="inline-flex items-center gap-2 bg-surface-container px-4 py-2.5 rounded-xl font-black text-[10px] text-on-surface-variant uppercase tracking-widest hover:bg-primary-container hover:text-white hover:shadow-lg transition-all active:scale-95 group/btn">
                                        <span class="material-symbols-outlined text-base group-hover/btn:fill-1">inventory</span>
                                        Kelola Gudang
                                    </a>
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
