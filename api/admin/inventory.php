<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

// --- LOGIKA PENCARIAN ---
$search_query = isset($_GET['q']) ? $_GET['q'] : '';
$search_q = $search_query; // alias untuk form input

// 1. Fetch Inventory from Firestore
$inventoryRef = $db->collection('inventory');
$query = $inventoryRef;

// Note: Firestore doesn't support partial string matching (LIKE) easily.
// For now, we fetch all and filter in PHP, or use standard equality if applicable.
$documents = $query->orderBy('stock', 'ASC')->documents();

$inventory_data = [];
$categories = [];
foreach ($documents as $doc) {
    if ($doc->exists()) {
        $data = $doc->data();
        $data['id'] = $doc->id();
        
        // Client-side search filtering
        if (!empty($search_query)) {
            if (stripos($data['item_name'], $search_query) === false && 
                stripos($data['category'], $search_query) === false) {
                continue;
            }
        }
        
        $inventory_data[] = $data;
        $categories[] = $data['category'];
    }
}

// 2. Fetch Master Templates
$master_templates = $db->collection('procurement_templates')->orderBy('product_name', 'ASC')->documents();
$templates_data = [];
foreach ($master_templates as $doc) {
    $templates_data[] = $doc->data();
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Inventory Management</title>
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
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen flex flex-col lg:flex-row">
    
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="flex-1 flex flex-col min-w-0 pt-14 lg:pt-0">
        <!-- Header Bar -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-10">
            <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface tracking-tight">Inventory Management</h1>
            <div class="flex items-center gap-4">
                <form action="" method="GET" class="relative group flex-1 sm:flex-none">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                        <span class="material-symbols-outlined text-xl">search</span>
                    </div>
                    <input name="q" value="<?php echo htmlspecialchars($search_q); ?>" 
                        class="block w-full sm:w-48 md:w-56 pl-11 pr-11 py-2 bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 transition-all outline-none font-medium text-sm placeholder:text-outline/60 dark:text-white" 
                        placeholder="Cari perangkat..." type="text"/>
                    <?php if(!empty($search_q)): ?>
                        <a href="inventory.php" class="absolute inset-y-0 right-0 pr-4 flex items-center text-outline hover:text-rose-500 transition-colors">
                            <span class="material-symbols-outlined text-sm">close</span>
                        </a>
                    <?php endif; ?>
                </form>
                <button onclick="toggleModal('modalTambah')" class="px-4 py-2.5 bg-gradient-to-br from-primary to-primary-container text-white font-headline font-bold rounded-2xl shadow-lg shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 transition-all active:scale-95 flex items-center gap-2 text-sm whitespace-nowrap">
                    <span class="material-symbols-outlined text-lg">add_box</span>
                    <span class="hidden sm:inline">Update / Tambah Stok</span>
                </button>
            </div>
        </header>

        <div class="p-4 md:p-8 space-y-6 md:space-y-8">
            <!-- Asymmetric Stats Grid -->
            <section class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Highlight Card -->
                <div class="md:col-span-2 bg-gradient-to-br from-indigo-900 to-indigo-950 p-8 rounded-[2.5rem] shadow-2xl shadow-indigo-900/10 text-white relative overflow-hidden flex flex-col justify-between">
                    <div class="absolute top-0 right-0 p-8 opacity-10 rotate-12">
                        <span class="material-symbols-outlined text-[140px]">inventory</span>
                    </div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 text-indigo-200 uppercase tracking-widest text-[10px] font-black mb-1">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Live Inventory Status
                        </div>
                        <h2 class="text-3xl font-bold leading-tight">Sinkronisasi stok gudang dengan Master Template.</h2>
                    </div>
                    <p class="relative z-10 text-indigo-100/60 text-xs font-medium max-w-sm mt-6 leading-relaxed">Kelola aset TI secara terpusat. Setiap perubahan stok fisik akan secara otomatis mengupdate estimasi biaya pemohon.</p>
                </div>

                <!-- Small Stats -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-outline-variant/5 flex flex-col justify-between group hover:shadow-md transition-all">
                    <div class="flex justify-between items-start">
                        <div class="p-3 bg-red-50 text-red-600 rounded-2xl group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined">warning</span>
                        </div>
                        <span class="text-[10px] font-black text-red-500 bg-red-50 px-3 py-1 rounded-full">LOW STOCK</span>
                    </div>
                    <div>
                        <?php 
                            $stat_low = 0;
                            foreach ($inventory_data as $inv) {
                                if ($inv['stock'] <= $inv['min_stock']) $stat_low++;
                            }
                        ?>
                        <h3 class="text-4xl font-black text-on-surface"><?php echo sprintf("%02d", $stat_low); ?></h3>
                        <p class="text-on-surface-variant text-xs font-bold mt-1 uppercase tracking-wider">Perangkat Perlu Restock</p>
                    </div>
                </div>

                <div class="bg-surface-container-high p-6 rounded-3xl flex flex-col justify-between group hover:bg-white hover:shadow-md transition-all">
                    <div class="flex justify-between items-start">
                        <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined">category</span>
                        </div>
                    </div>
                    <div>
                        <?php 
                            $stat_cat = count(array_unique($categories));
                        ?>
                        <h3 class="text-4xl font-black text-on-surface"><?php echo sprintf("%02d", $stat_cat); ?></h3>
                        <p class="text-on-surface-variant text-xs font-bold mt-1 uppercase tracking-wider">Kategori Hardware</p>
                    </div>
                </div>
            </section>

            <!-- Asset Table -->
            <section class="bg-white rounded-[2.5rem] border border-outline-variant/5 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-outline-variant/10 flex items-center justify-between">
                    <div>
                        <h3 class="font-headline text-xl font-bold text-on-surface italic uppercase tracking-tight">Asset <span class="text-primary">Repository</span></h3>
                        <p class="text-xs text-on-surface-variant mt-1 font-medium">Daftar lengkap perangkat yang tersedia di gudang TI.</p>
                    </div>
                    <div class="flex gap-2">
                         <span class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-outline hover:text-primary cursor-pointer transition-colors">
                            <span class="material-symbols-outlined text-xl">tune</span>
                         </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/30 border-b border-outline-variant/10">
                                <th class="px-8 py-5">Detail Perangkat</th>
                                <th class="px-8 py-5">Kategori</th>
                                <th class="px-8 py-5 text-right">Pricing (User)</th>
                                <th class="px-8 py-5 text-center">Qty / Satuan</th>
                                <th class="px-8 py-5 text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <?php foreach($inventory_data as $row): 
                                $harga_dasar = $row['harga_master'] ?? $row['price_reference'] ?? 0;
                                $total_user = $harga_dasar + ($harga_dasar * 0.10) + ($harga_dasar * 0.05);
                            ?>
                            <tr class="group hover:bg-surface-container-lowest transition-all">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-on-surface leading-snug group-hover:text-primary transition-colors"><?php echo htmlspecialchars($row['item_name']); ?></span>
                                        <p class="text-[10px] text-outline font-medium mt-1 uppercase tracking-tighter truncate max-w-[240px]"><?php echo htmlspecialchars($row['specification'] ?? 'Karakteristik belum didefinisikan'); ?></p>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-100 uppercase tracking-widest">
                                        <?php echo htmlspecialchars($row['category']); ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="text-[10px] text-outline font-bold uppercase mb-0.5">EST. FINAL</span>
                                        <span class="font-headline font-black text-on-surface italic">Rp <?php echo number_format($total_user, 0, ',', '.'); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="text-2xl font-black <?php echo $row['stock'] <= ($row['min_stock'] ?? 0) ? 'text-error animate-pulse' : 'text-on-surface'; ?>"><?php echo $row['stock']; ?></span>
                                        <span class="text-[10px] font-black text-outline/60 uppercase tracking-widest"><?php echo strtoupper($row['satuan'] ?? 'UNIT'); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-x-4 group-hover:translate-x-0">
                                        <button onclick="bukaModalEdit(<?php echo htmlspecialchars(json_encode($row)); ?>)" class="w-10 h-10 rounded-xl bg-primary-fixed/30 text-primary flex items-center justify-center hover:bg-primary hover:text-white shadow-sm transition-all">
                                            <span class="material-symbols-outlined text-lg">edit</span>
                                        </button>
                                        <a href="../config/hapus_inventory.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Peringatan: Menghapus item dari gudang akan menghentikan sinkronisasi stok!')" 
                                           class="w-10 h-10 rounded-xl bg-error-container/20 text-error flex items-center justify-center hover:bg-error hover:text-white transition-all">
                                            <span class="material-symbols-outlined text-lg">delete</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <!-- Modern Modal: Add / Update Stock -->
        <div id="modalTambah" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[100] p-6 backdrop-blur-md transition-all duration-500">
            <div class="bg-white rounded-[3rem] max-w-lg w-full p-10 shadow-3xl overflow-hidden relative" id="modalContent">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full -mr-16 -mt-16"></div>
                
                <div class="relative flex justify-between items-center mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-primary-fixed flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined">input</span>
                        </div>
                        <h3 class="font-headline text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Update <span class="text-primary italic">Stok Gudang</span></h3>
                    </div>
                    <button onclick="toggleModal('modalTambah')" class="w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-outline transition-all">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form action="../config/proses_inventory_lengkap.php" method="POST" class="space-y-6 relative">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-on-surface-variant uppercase tracking-widest ml-1">Pilih Produk (Master Template)</label>
                        <select id="selectTemplate" name="item_name" onchange="autoFillTemplate()" required 
                            class="w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 appearance-none outline-none font-bold text-primary transition-all">
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
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-on-surface-variant uppercase tracking-widest ml-1">Kategori (Auto)</label>
                            <input type="text" id="disp_category" name="category" readonly 
                                class="w-full px-5 py-4 bg-surface-container-highest border-0 rounded-2xl font-bold text-on-surface-variant italic cursor-not-allowed">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-on-surface-variant uppercase tracking-widest ml-1">Satuan</label>
                            <input type="text" name="satuan" placeholder="Unit/Pcs" required 
                                class="w-full px-5 py-4 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 outline-none font-bold text-on-surface">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-primary uppercase tracking-widest ml-1">Tambah Jumlah Stok</label>
                            <input type="number" name="stock" required 
                                class="w-full px-5 py-4 bg-primary-fixed/20 border-2 border-primary-fixed rounded-2xl focus:border-primary focus:ring-0 outline-none font-black text-2xl text-primary text-center">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-error uppercase tracking-widest ml-1">Min. Stok Alert</label>
                            <input type="number" name="min_stock" value="5" 
                                class="w-full px-5 py-4 bg-error-container/20 border-2 border-error-container/50 rounded-2xl focus:border-error focus:ring-0 outline-none font-black text-2xl text-error text-center">
                        </div>
                    </div>

                    <div class="bg-surface-container rounded-2x p-6 border border-outline-variant/20 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-outline uppercase tracking-widest">Base Price Reference</span>
                            <span id="disp_price" class="text-xl font-headline font-black text-on-surface-variant italic leading-none mt-1">Rp 0</span>
                        </div>
                        <input type="hidden" name="price" id="hidden_price">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary scale-110">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                    </div>

                    <button type="submit" name="simpan_inventory" class="w-full bg-gradient-to-br from-indigo-600 to-primary text-white font-headline font-black py-5 rounded-2xl shadow-xl hover:shadow-primary/40 hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-xs">
                        Update Stok Gudang
                    </button>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT INVENTORY (Similar Modern Style) -->
        <div id="modalEdit" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[110] p-6 backdrop-blur-md">
            <div class="bg-white rounded-[3rem] max-w-lg w-full p-10 shadow-3xl overflow-hidden relative" id="modalEditContent">
                <div class="absolute top-0 right-0 w-32 h-32 bg-secondary/5 rounded-full -mr-16 -mt-16"></div>
                
                <div class="relative flex justify-between items-center mb-8">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-secondary-fixed flex items-center justify-center text-secondary">
                            <span class="material-symbols-outlined text-xl">stylus_note</span>
                        </div>
                        <h3 class="font-headline text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Revisi <span class="text-secondary italic">Stok Barang</span></h3>
                    </div>
                    <button onclick="tutupModalEdit()" class="w-10 h-10 rounded-full hover:bg-surface-container flex items-center justify-center text-outline transition-all">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <form action="../config/proses_inventory_lengkap.php" method="POST" class="space-y-6 relative">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="item_id" id="edit_id">
                    
                    <div class="space-y-2">
                        <label class="block text-xs font-black text-on-surface-variant uppercase tracking-widest ml-1">Nama Barang (Read-Only)</label>
                        <input type="text" name="item_name" id="edit_name" readonly 
                            class="w-full px-5 py-4 bg-surface-container-highest border-0 rounded-2xl font-bold text-on-surface-variant italic cursor-not-allowed">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-on-surface-variant uppercase tracking-widest ml-1">Kategori (Read-Only)</label>
                            <input type="text" name="category" id="edit_category" readonly 
                                class="w-full px-5 py-4 bg-surface-container-highest border-0 rounded-2xl font-bold text-on-surface-variant italic cursor-not-allowed">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-secondary uppercase tracking-widest ml-1">Satuan</label>
                            <input type="text" name="satuan" id="edit_satuan" required 
                                class="w-full px-5 py-4 bg-secondary-fixed/10 border-2 border-secondary-fixed rounded-2xl focus:border-secondary focus:ring-0 outline-none font-bold text-secondary">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-secondary uppercase tracking-widest ml-1">Revisi Sisa Stok Fisik</label>
                            <input type="number" name="stock" id="edit_stock" required 
                                class="w-full px-5 py-4 bg-secondary-fixed/20 border-2 border-secondary-fixed rounded-2xl focus:border-secondary focus:ring-0 outline-none font-black text-3xl text-secondary text-center">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-xs font-black text-error uppercase tracking-widest ml-1">Min. Stok Alert</label>
                            <input type="number" name="min_stock" id="edit_min" 
                                class="w-full px-5 py-4 bg-error-container/10 border-2 border-error-container/30 rounded-2xl focus:border-error focus:ring-0 outline-none font-black text-3xl text-error text-center">
                        </div>
                    </div>

                    <div class="bg-surface-container rounded-2x p-6 border border-outline-variant/20 relative group">
                        <label class="block text-[10px] font-black text-outline uppercase tracking-widest mb-1">Update Dasar Harga Master (Opsional)</label>
                        <div class="flex items-center gap-3">
                            <span class="font-headline font-black text-on-surface-variant opacity-60">Rp</span>
                            <input type="number" name="price" id="edit_price" 
                                class="w-full bg-transparent border-0 outline-none font-headline font-black text-xl text-on-surface p-0 focus:ring-0 transition-all group-focus-within:text-primary">
                        </div>
                    </div>

                    <button type="submit" name="simpan_inventory" class="w-full bg-secondary text-white font-headline font-black py-5 rounded-2xl shadow-xl hover:shadow-secondary/40 hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-[0.2em] text-xs">
                        Simpan Perubahan Gudang
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function toggleModal(id) {
            const modal = document.getElementById(id);
            const content = id === 'modalTambah' ? document.getElementById('modalContent') : document.getElementById('modalEditContent');
            if(modal.classList.contains('hidden')) {
                modal.classList.replace('hidden', 'flex');
                setTimeout(() => {
                    content.classList.replace('scale-95', 'scale-100');
                    content.classList.remove('opacity-0');
                }, 10);
            } else {
                content.classList.replace('scale-100', 'scale-95');
                content.classList.add('opacity-0');
                setTimeout(() => modal.classList.replace('flex', 'hidden'), 300);
            }
        }

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
                content.classList.remove('opacity-0');
            }, 10);
        }

        function tutupModalEdit() {
            toggleModal('modalEdit');
        }
    </script>
</body>
</html>
