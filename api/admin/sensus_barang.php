<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';
require_once __DIR__ . '/../includes/pagination_helper.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

// 1. Check Active Batch
$active_batch = null;
$tasks = [];
$stats = ['Pending' => 0, 'Reported' => 0, 'Finalized' => 0];

try {
    $batchDocs = $db->collection('sensus_batches')->where('status', '=', 'Active')->limit(1)->documents();
    if (!$batchDocs->isEmpty()) {
        foreach ($batchDocs as $b) {
            $active_batch = $b->data();
            $active_batch['id'] = $b->id();
        }

        // Fetch all tasks for active batch
        $taskDocs = $db->collection('sensus_tasks')->where('batch_id', '=', $active_batch['id'])->documents();
        foreach ($taskDocs as $t) {
            $data = $t->data();
            $data['id'] = $t->id();
            $tasks[] = $data;
            if (isset($stats[$data['status']])) $stats[$data['status']]++;
        }
    }
} catch (Exception $e) {}

// 2. Fetch Users to mapping for Hierarchical Grouping (Positions)
$user_positions = [];
try {
    $uDocs = $db->collection('users')->documents();
    foreach ($uDocs as $ud) {
        $u = $ud->data();
        $user_positions[$u['username']] = $u['jabatan'] ?? 'Staff';
    }
} catch (Exception $e) {}

// Hierarchical Grouping Logic
$grouped_tasks = [];
foreach ($tasks as $task) {
    $dept = $task['department'];
    $user_id = $task['user_id'];
    $jabatan = $user_positions[$user_id] ?? 'Staff';

    if (!isset($grouped_tasks[$dept])) $grouped_tasks[$dept] = ['Kabid' => [], 'Staff' => [], 'Other' => []];

    if (stripos($jabatan, 'Bidang') !== false) {
        $category = 'Kabid';
    } elseif (stripos($jabatan, 'Staff') !== false) {
        $category = 'Staff';
    } else {
        $category = 'Other';
    }

    $grouped_tasks[$dept][$category][] = $task;
}

$pageTitle = 'SIDIK-TI | Manajemen Sensus';
$base_url = '../';
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <?php include __DIR__ . '/../includes/head_meta.php'; ?>
    <style>
        .bento-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .dept-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .dept-card:hover { transform: translateY(-4px); }
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased min-h-screen">
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen">
        <header class="px-8 py-8 border-b border-outline/5 bg-white/50 backdrop-blur-xl sticky top-0 z-20 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-black tracking-tight italic uppercase">Manajemen <span class="text-primary italic">Sensus</span></h1>
                <p class="text-xs text-on-surface-variant font-medium mt-1">Kelola periode inspeksi dan monitoring progres pelaporan hirarkis.</p>
            </div>
            
            <?php if (!$active_batch): ?>
                <button onclick="document.getElementById('modalStartBatch').classList.replace('hidden', 'flex')" 
                        class="px-6 py-3 bg-primary text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-primary/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">add_task</span> Mulai Batch Baru
                </button>
            <?php else: ?>
                <div class="px-5 py-3 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center gap-3">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-emerald-700">Batch Aktif: <?php echo htmlspecialchars($active_batch['batch_name']); ?></span>
                </div>
            <?php endif; ?>
        </header>

        <div class="p-8 space-y-10">
            <?php if ($active_batch): ?>
                <!-- Stats Overview -->
                <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="obsidian-panel p-6 rounded-3xl border-outline/5 flex justify-between items-center group">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant/40 mb-1">Total Aset</p>
                            <h3 class="text-4xl font-black text-on-surface tracking-tighter"><?php echo count($tasks); ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-surface-low rounded-2xl flex items-center justify-center text-on-surface-variant group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined">inventory_2</span>
                        </div>
                    </div>
                    <div class="obsidian-panel p-6 rounded-3xl border-primary/20 flex justify-between items-center group bg-primary/5">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-primary/60 mb-1">Sudah Melapor</p>
                            <h3 class="text-4xl font-black text-primary tracking-tighter"><?php echo $stats['Reported'] + $stats['Finalized']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-primary/10 text-primary rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined">check_circle</span>
                        </div>
                    </div>
                    <div class="obsidian-panel p-6 rounded-3xl border-orange-200 flex justify-between items-center group">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest text-orange-400/60 mb-1">Menunggu</p>
                            <h3 class="text-4xl font-black text-orange-600 tracking-tighter"><?php echo $stats['Pending']; ?></h3>
                        </div>
                        <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined">hourglass_empty</span>
                        </div>
                    </div>
                </section>

                <!-- Hierarchical List -->
                <section class="space-y-8">
                    <h2 class="text-sm font-black uppercase tracking-[0.3em] text-on-surface-variant flex items-center gap-3">
                        <span class="w-8 h-[1px] bg-outline/20"></span>
                        Klaster Hirarki Bidang
                        <span class="w-full h-[1px] bg-outline/20"></span>
                    </h2>

                    <div class="space-y-6">
                        <?php foreach ($grouped_tasks as $dept => $categories): ?>
                            <div class="bg-white rounded-[2.5rem] border border-outline/5 shadow-sm overflow-hidden dept-card">
                                <div class="px-8 py-6 bg-slate-50/50 border-b border-outline/5 flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-on-surface text-surface rounded-xl flex items-center justify-center">
                                            <span class="material-symbols-outlined text-xl">account_balance</span>
                                        </div>
                                        <h3 class="font-black text-lg uppercase italic tracking-tight"><?php echo htmlspecialchars($dept); ?></h3>
                                    </div>
                                    <span class="px-4 py-1.5 bg-white rounded-full text-[10px] font-black border border-outline/10 text-on-surface-variant uppercase tracking-widest">
                                        <?php echo count($categories['Kabid']) + count($categories['Staff']) + count($categories['Other']); ?> Aset
                                    </span>
                                </div>
                                
                                <div class="p-8 space-y-8">
                                    <!-- Kabid Cluster -->
                                    <?php if (!empty($categories['Kabid'])): ?>
                                        <div class="space-y-4">
                                            <p class="text-[9px] font-black text-primary uppercase tracking-[0.2em] ml-4">Grup Kepala Bidang</p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <?php foreach ($categories['Kabid'] as $task): ?>
                                                    <?php renderAdminTaskCard($task); ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Staff Cluster -->
                                    <?php if (!empty($categories['Staff'])): ?>
                                        <div class="space-y-4">
                                            <p class="text-[9px] font-black text-on-surface-variant/60 uppercase tracking-[0.2em] ml-4">Grup Pelaksana / Staff</p>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <?php foreach ($categories['Staff'] as $task): ?>
                                                    <?php renderAdminTaskCard($task); ?>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center py-32 text-center obsidian-panel rounded-[3rem] border-dashed border-2">
                    <div class="w-24 h-24 bg-surface-low rounded-[2rem] flex items-center justify-center mb-6 text-on-surface-variant/20">
                        <span class="material-symbols-outlined text-5xl">inventory</span>
                    </div>
                    <h2 class="text-2xl font-black text-on-surface italic uppercase">Belum Ada Batch Sensus</h2>
                    <p class="text-on-surface-variant max-w-sm mt-3 font-medium">Buat batch sensus baru untuk mendeteksi aset aktif dan mulai pengumpulan laporan dari user.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Finalize Task -->
    <div id="modalFinalize" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[100] p-6 backdrop-blur-sm transition-all">
        <div class="bg-white rounded-[2.5rem] max-w-md w-full p-10 shadow-2xl relative">
            <button onclick="tutupModalFinalize()" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-500">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="mb-8 flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-emerald-50 text-emerald-600 flex items-center justify-center rounded-3xl mb-4 border border-emerald-100">
                    <span class="material-symbols-outlined text-3xl">verified</span>
                </div>
                <h3 class="font-headline font-black text-2xl italic uppercase text-on-surface">Validasi <span class="text-emerald-500 italic">Sensus</span></h3>
                <p class="text-xs text-on-surface-variant mt-2" id="finalize_item_name"></p>
            </div>

            <form action="../config/proses_sensus.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="finalize_task">
                <input type="hidden" name="task_id" id="finalize_task_id">
                <input type="hidden" name="asset_id" id="finalize_asset_id">

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Laporan User</p>
                    <p class="text-sm font-bold text-slate-700" id="user_report_hint"></p>
                </div>
                
                <div>
                    <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-widest mb-3 ml-2">Final Kondisi Fisik (%)</label>
                    <input type="number" name="final_pct" id="final_pct" min="0" max="100" required 
                           class="w-full py-5 bg-slate-50 border-0 rounded-3xl focus:ring-4 focus:ring-emerald-500/10 outline-none font-black text-3xl text-center text-emerald-600">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-widest mb-3 ml-2">Kesimpulan Admin</label>
                    <textarea name="final_notes" rows="3" required class="w-full p-5 bg-slate-50 border-0 rounded-3xl focus:ring-4 focus:ring-emerald-500/10 outline-none font-medium text-sm text-on-surface"></textarea>
                </div>

                <button type="submit" class="w-full bg-emerald-600 text-white font-black py-5 rounded-3xl shadow-xl shadow-emerald-900/20 hover:scale-[1.02] transition-all uppercase tracking-widest text-xs">
                    Selesaikan & Update Inventaris
                </button>
            </form>
        </div>
    </div>

    <!-- Modal Start Batch -->
    <div id="modalStartBatch" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[100] p-6 backdrop-blur-sm transition-all">
        <div class="bg-white rounded-[2.5rem] max-w-md w-full p-10 shadow-2xl relative border border-outline/10">
            <button onclick="document.getElementById('modalStartBatch').classList.replace('flex', 'hidden')" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-500">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="mb-8 flex flex-col items-center">
                <div class="w-16 h-16 bg-primary/10 text-primary flex items-center justify-center rounded-3xl mb-4">
                    <span class="material-symbols-outlined text-3xl">rocket_launch</span>
                </div>
                <h3 class="font-black text-2xl uppercase italic text-on-surface">Buka <span class="text-primary italic">Batch Sensus</span></h3>
            </div>
            <form action="../config/proses_sensus.php" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="action" value="start_batch">
                <div>
                    <label class="block text-[10px] font-black text-on-surface-variant uppercase tracking-widest mb-3 ml-2">Nama Batch Sensus</label>
                    <input type="text" name="batch_name" required placeholder="E.g. Sensus Semester 1 2026" 
                           class="w-full p-5 bg-slate-50 border-0 rounded-3xl focus:ring-4 focus:ring-primary/10 outline-none font-bold text-sm text-on-surface">
                </div>
                <button type="submit" class="w-full bg-primary text-white font-black py-5 rounded-3xl shadow-xl shadow-primary/30 hover:scale-[1.02] transition-all uppercase tracking-[0.2em] text-xs">
                    Deploy Batch Sekarang
                </button>
            </form>
        </div>
    </div>

    <script>
        function bukaModalFinalize(taskId, assetId, name, reportPct, notes) {
            document.getElementById('finalize_task_id').value = taskId;
            document.getElementById('finalize_asset_id').value = assetId;
            document.getElementById('finalize_item_name').innerText = name;
            document.getElementById('user_report_hint').innerText = "Persentase: " + reportPct + "% | Catatan: " + notes;
            document.getElementById('final_pct').value = reportPct;
            document.getElementById('modalFinalize').classList.replace('hidden', 'flex');
        }
        function tutupModalFinalize() { document.getElementById('modalFinalize').classList.replace('flex', 'hidden'); }
    </script>
</body>
</html>

<?php
/**
 * Helper to render sub-cards for tasks in the admin list
 */
function renderAdminTaskCard($task) {
    if ($task['status'] === 'Finalized') {
        $statusClass = 'bg-emerald-50 text-emerald-600 border-emerald-100';
    } elseif ($task['status'] === 'Reported') {
        $statusClass = 'bg-indigo-50 text-primary border-primary/20 animate-pulse';
    } else {
        $statusClass = 'bg-slate-50 text-slate-400 border-slate-100';
    }
?>
    <div class="p-6 bg-white border border-outline/5 rounded-3xl hover:border-primary/20 transition-all group flex items-start justify-between">
        <div class="flex gap-4">
            <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400 group-hover:bg-primary/5 group-hover:text-primary transition-all">
                <span class="material-symbols-outlined">person</span>
            </div>
            <div>
                <p class="font-bold text-sm text-on-surface"><?php echo htmlspecialchars($task['user_name']); ?></p>
                <p class="text-[10px] font-black text-on-surface-variant uppercase tracking-widest mt-0.5"><?php echo htmlspecialchars($task['item_name']); ?></p>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase border <?php echo $statusClass; ?>">
                        <?php echo $task['status']; ?>
                    </span>
                    <?php if ($task['status'] === 'Reported'): ?>
                        <span class="text-[10px] font-black text-primary ml-2"><?php echo $task['report_pct']; ?>% Condition</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($task['status'] === 'Reported'): ?>
            <button onclick="bukaModalFinalize('<?php echo $task['id']; ?>', '<?php echo $task['asset_id']; ?>', '<?php echo htmlspecialchars(addslashes($task['item_name'])); ?>', '<?php echo $task['report_pct']; ?>', '<?php echo htmlspecialchars(addslashes($task['report_notes']??'')); ?>')" 
                    class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center hover:bg-primary hover:text-white transition-all shadow-md">
                <span class="material-symbols-outlined">edit_note</span>
            </button>
        <?php endif; ?>
    </div>
<?php } ?>
