<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// Proses Tambah/Update Anggaran Departemen
if (isset($_POST['save_budget'])) {
    require_csrf_token();
    
    $year = (int)$_POST['fiscal_year'];
    $dept = $_POST['department'];
    $limit = (float)$_POST['total_limit'];
    
    // Firestore "UPSERT" pattern
    $budgetRef = $db->collection('budget_config')
        ->where('fiscal_year', '=', $year)
        ->where('department', '=', $dept)
        ->limit(1)
        ->documents();
        
    if ($budgetRef->isEmpty()) {
        $db->collection('budget_config')->add([
            'fiscal_year' => $year,
            'department' => $dept,
            'total_limit' => $limit,
            'used_amount' => 0
        ]);
    } else {
        foreach ($budgetRef as $doc) {
            $doc->reference()->update([
                ['path' => 'total_limit', 'value' => $limit]
            ]);
        }
    }
}

// Ambil data budget per departemen secara dinamis
$current_year = date('Y');
$budgets = $db->collection('budget_config')
    ->where('fiscal_year', '=', (int)$current_year)
    ->documents();

// Ambil daftar departemen untuk dropdown
$q_dept = $db->collection('departments')->orderBy('nama_dept', 'ASC')->documents();
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Financial Allocation Control</title>
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
<body class="bg-surface font-body text-on-surface antialiased flex min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="flex-1 flex flex-col min-w-0">
        <!-- Header Bar -->
        <header class="flex items-center justify-between px-8 py-5 border-b border-outline-variant/10 sticky top-0 bg-surface/80 backdrop-blur-xl z-20">
            <div>
                <h1 class="font-headline text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">Fiscal <span class="text-primary italic">Control</span></h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest mt-1">Manajemen Alokasi & Pagu Anggaran Departemen</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="px-5 py-2.5 bg-white border border-outline-variant/20 rounded-2xl shadow-sm flex items-center gap-3 group transition-all hover:border-primary/30">
                    <span class="material-symbols-outlined text-primary text-xl">calendar_today</span>
                    <span class="text-xs font-black text-on-surface uppercase tracking-widest">Fiscal Year <?php echo date('Y'); ?></span>
                </div>
            </div>
        </header>

        <div class="p-8 space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                
                <!-- Budget Setting Panel -->
                <div class="lg:col-span-4 h-fit">
                    <section class="bg-white p-8 rounded-[2.5rem] border border-outline-variant/10 shadow-2xl shadow-indigo-900/5 relative overflow-hidden group">
                        <div class="absolute top-0 left-0 w-1.5 h-full bg-primary/20 group-hover:bg-primary transition-all duration-500"></div>
                        
                        <h2 class="flex items-center gap-3 font-headline text-lg font-black text-on-surface uppercase tracking-tight mb-8">
                            <span class="material-symbols-outlined text-primary fill-1">payments</span>
                            Set Alokasi Pagu
                        </h2>

                        <form action="" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="fiscal_year" value="<?php echo date('Y'); ?>">
                            
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Departemen Tujuan</label>
                                <div class="relative group">
                                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-primary transition-colors text-lg">apartment</span>
                                    <select name="department" required class="block w-full pl-12 pr-10 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 appearance-none transition-all text-sm">
                                        <option value="">-- Pilih Unit --</option>
                                        <?php foreach ($q_dept as $doc): 
                                            $dept2 = $doc->data(); ?>
                                        <option value="<?php echo htmlspecialchars($dept2['nama_dept']); ?>"><?php echo htmlspecialchars($dept2['nama_dept']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-outline uppercase tracking-[0.2em] ml-2">Total Limit Pagu (Rp)</label>
                                <div class="relative group/price">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-black text-primary group-focus-within/price:scale-110 transition-transform">Rp</span>
                                    <input type="number" name="total_limit" required placeholder="0" 
                                        class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl font-black text-primary outline-none focus:ring-2 focus:ring-primary/20 transition-all text-base tracking-tight">
                                </div>
                            </div>
                            
                            <button type="submit" name="save_budget" class="w-full py-5 bg-gradient-to-br from-indigo-600 to-indigo-500 text-white font-headline font-black rounded-2xl shadow-xl shadow-indigo-900/10 hover:shadow-indigo-900/30 hover:-translate-y-1 transition-all active:scale-[0.98] uppercase tracking-widest text-xs flex items-center justify-center gap-3 group/btn">
                                <span class="material-symbols-outlined text-lg fill-1 group-hover/btn:scale-110 transition-transform">analytics</span>
                                Simpan Konfigurasi
                            </button>
                        </form>

                        <div class="mt-8 p-6 bg-surface-container-low rounded-3xl border border-outline-variant/10 flex items-start gap-4">
                            <span class="material-symbols-outlined text-primary text-xl">info</span>
                            <p class="text-[10px] text-on-surface-variant font-bold leading-relaxed uppercase tracking-tight">
                                Pagu anggaran adalah batas maksimal pengadaan per departemen dalam satu tahun anggaran. Update otomatis memengaruhi tiket baru.
                            </p>
                        </div>
                    </section>
                </div>

                <!-- Budget Monitor Panel -->
                <div class="lg:col-span-8 space-y-6">
                    <section class="grid grid-cols-1 gap-4">
                        <div class="flex items-center justify-between px-4 mb-2">
                            <h2 class="font-headline text-xl font-black text-on-surface italic uppercase tracking-tighter">Budget <span class="text-primary italic">Distribution</span></h2>
                        </div>

                        <?php foreach($budgets as $doc): 
                            $b = $doc->data();
                            $sisa = $b['total_limit'] - $b['used_amount'];
                            $persen = ($b['total_limit'] > 0) ? ($b['used_amount'] / $b['total_limit']) * 100 : 0;
                            
                            $bar_color = "bg-primary";
                            if($persen > 80) $bar_color = "bg-orange-500";
                            if($persen > 95) $bar_color = "bg-error";
                        ?>
                        <div class="bg-white p-6 rounded-[2rem] border border-outline-variant/10 shadow-sm hover:shadow-xl hover:shadow-indigo-900/5 transition-all group flex items-center gap-6 relative overflow-hidden">
                            <!-- Visual Progress BG -->
                            <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                            
                            <div class="w-16 h-16 rounded-2xl bg-surface-container-high border border-outline-variant/10 text-primary font-headline font-black text-base flex items-center justify-center uppercase italic shrink-0 group-hover:bg-primary group-hover:text-white transition-all shadow-sm">
                                <?php echo substr($b['department'], 0, 2); ?>
                            </div>

                            <div class="flex-1 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-headline font-extrabold text-on-surface uppercase italic tracking-tight group-hover:text-primary transition-colors"><?php echo $b['department']; ?></h4>
                                    <div class="text-right">
                                        <span class="block text-[9px] font-black text-outline uppercase tracking-widest leading-none">Anggaran Sisa</span>
                                        <span class="block text-sm font-black text-on-surface mt-1">Rp <?php echo number_format($sisa, 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-tighter">
                                        <span class="text-outline">Utilization</span>
                                        <span class="<?php echo $persen > 80 ? 'text-orange-600' : 'text-primary'; ?>"><?php echo round($persen, 1); ?>%</span>
                                    </div>
                                    <div class="w-full bg-surface-container h-2 rounded-full overflow-hidden border border-outline-variant/5">
                                        <div class="<?php echo $bar_color; ?> h-full rounded-full transition-all duration-1000 group-hover:brightness-110" style="width: <?php echo $persen; ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-right pl-6 border-l border-outline-variant/10 shrink-0">
                                <p class="text-[9px] font-black text-outline uppercase tracking-widest leading-none">Pagu Total</p>
                                <p class="text-base font-headline font-black text-primary mt-1">Rp <?php echo number_format($b['total_limit'], 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if($budgets->isEmpty()): ?>
                            <div class="py-20 text-center bg-white rounded-[2rem] border border-outline-variant/10 border-dashed">
                                <span class="material-symbols-outlined text-[64px] text-outline opacity-30 mb-4 scale-125">account_balance_wallet</span>
                                <p class="font-headline font-black text-outline uppercase tracking-[0.2em] text-xs">No Fiscal Data for <?php echo date('Y'); ?></p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>

            </div>
        </div>
    </main>

</body>
</html>