<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$tasks = [];
$active_batch = null;

if ($db) {
    try {
        $batchDocs = $db->collection('sensus_batches')->where('status', '=', 'Active')->limit(1)->documents();
        if (!$batchDocs->isEmpty()) {
            foreach ($batchDocs as $b) {
                $active_batch = $b->data(); $active_batch['id'] = $b->id();
            }
            $taskDocs = $db->collection('sensus_tasks')->where('batch_id', '=', $active_batch['id'])->where('user_id', '=', $user_id)->documents();
            foreach ($taskDocs as $t) {
                $data = $t->data(); $data['id'] = $t->id();
                $tasks[] = $data;
            }
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $uid_e = mysqli_real_escape_string($conn, $user_id);
    $res_batch = mysqli_query($conn, "SELECT * FROM sensus_batches WHERE status = 'Active' LIMIT 1");
    if ($row = mysqli_fetch_assoc($res_batch)) {
        $active_batch = $row;
        $batch_id_e = mysqli_real_escape_string($conn, $active_batch['id']);
        $res_tasks = mysqli_query($conn, "SELECT * FROM sensus_tasks WHERE batch_id = '$batch_id_e' AND user_id = '$uid_e'");
        while ($t_row = mysqli_fetch_assoc($res_tasks)) { $tasks[] = $t_row; }
    }
}

$pageTitle = 'Sensus Mandiri Aset';
$base_url = '../';
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <?php include __DIR__ . '/../includes/head_meta.php'; ?>
    <style>
        .glass-card { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .dark .glass-card { background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="bg-surface dark:bg-slate-950 font-body text-on-surface dark:text-slate-100 antialiased">
    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>
    <main class="max-w-5xl mx-auto px-6 py-12 space-y-10">
        <header class="space-y-2">
            <h1 class="text-4xl font-extrabold tracking-tighter uppercase italic text-on-surface dark:text-white">Sensus <span class="text-primary dark:text-indigo-400">Mandiri</span></h1>
            <p class="text-on-surface-variant dark:text-slate-400 font-medium">Laporkan kondisi fisik aset yang sedang Anda gunakan untuk validasi sistem.</p>
        </header>

        <?php if (!$active_batch): ?>
            <div class="p-12 text-center obsidian-panel bg-white dark:bg-slate-900 rounded-3xl border-dashed border-2 border-outline/20 dark:border-slate-800">
                <span class="material-symbols-outlined text-6xl text-on-surface-variant/20 dark:text-slate-600 mb-4">event_busy</span>
                <h3 class="text-xl font-bold text-on-surface dark:text-slate-200">Tidak Ada Sensus Aktif</h3>
                <p class="text-sm text-on-surface-variant dark:text-slate-400 mt-2">Saat ini tidak ada periode sensus yang perlu dilaporkan.</p>
            </div>
        <?php else: ?>
            <div class="bg-primary/5 dark:bg-indigo-500/10 border border-primary/10 dark:border-indigo-500/20 p-6 rounded-3xl flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary dark:bg-indigo-500 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-primary/20">
                        <span class="material-symbols-outlined">campaign</span>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-primary/60 dark:text-indigo-400/80">Periode Aktif</p>
                        <h4 class="font-bold text-on-surface dark:text-white"><?php echo htmlspecialchars($active_batch['batch_name']); ?></h4>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($tasks as $task): ?>
                    <div class="glass-card bg-white dark:bg-slate-900 p-8 rounded-[2rem] shadow-sm hover:shadow-xl transition-all border border-outline/5 dark:border-slate-800 relative overflow-hidden group">
                        <?php if ($task['status'] === 'Reported' || $task['status'] === 'Finalized'): ?>
                            <div class="absolute top-0 right-0 px-6 py-2 bg-emerald-500 dark:bg-emerald-600 text-white text-[10px] font-black uppercase tracking-widest rounded-bl-3xl">Selesai</div>
                        <?php endif; ?>
                        <div class="flex items-center gap-5 mb-6">
                            <div class="w-14 h-14 bg-surface-container-low dark:bg-slate-800 rounded-2xl flex items-center justify-center text-primary dark:text-indigo-400 border border-outline/5 dark:border-slate-700">
                                <span class="material-symbols-outlined text-3xl">devices</span>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-on-surface dark:text-white leading-tight"><?php echo htmlspecialchars($task['item_name']); ?></h3>
                                <p class="text-xs text-on-surface-variant dark:text-slate-400 opacity-60 uppercase tracking-widest mt-1">ID: <?php echo htmlspecialchars($task['asset_id']); ?></p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-on-surface-variant dark:text-slate-400 font-medium">Status Pengajuan</span>
                                <span class="font-black <?php echo $task['status'] === 'Pending' ? 'text-orange-500' : 'text-emerald-500'; ?> uppercase tracking-widest"><?php echo $task['status']; ?></span>
                            </div>
                            <?php if ($task['status'] === 'Pending'): ?>
                                <button onclick="bukaModalSensus('<?php echo $task['id']; ?>', '<?php echo htmlspecialchars($task['item_name']); ?>')" class="w-full py-4 bg-primary dark:bg-indigo-600 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] shadow-lg shadow-primary/20 dark:shadow-indigo-900/50 hover:scale-[1.02] active:scale-95 transition-all">Mulai Laporan</button>
                            <?php else: ?>
                                <div class="p-4 bg-surface-container-low dark:bg-slate-800 rounded-2xl border border-outline/5 dark:border-slate-700">
                                    <p class="text-[9px] font-black text-on-surface-variant dark:text-slate-500 uppercase tracking-widest mb-1">Kondisi Terlapor</p>
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 flex-1 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden"><div class="h-full bg-emerald-500" style="width: <?php echo $task['report_pct']; ?>%"></div></div>
                                        <span class="text-xs font-bold text-on-surface dark:text-slate-300"><?php echo $task['report_pct']; ?>%</span>
                                    </div>
                                    <p class="text-[10px] text-on-surface-variant dark:text-slate-400 italic mt-2">"<?php echo htmlspecialchars($task['report_notes'] ?? '-'); ?>"</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($tasks)): ?>
                    <div class="col-span-full py-20 text-center obsidian-panel bg-white dark:bg-slate-900 rounded-[2.5rem]"><span class="material-symbols-outlined text-5xl opacity-10 dark:text-slate-600">assignment_turned_in</span><p class="text-on-surface-variant dark:text-slate-400 font-medium mt-4">Anda tidak memiliki aset yang perlu disensus pada periode ini.</p></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <div id="modalSensus" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[100] p-6 backdrop-blur-sm transition-all">
        <div class="bg-surface dark:bg-slate-900 rounded-[2.5rem] max-w-md w-full p-10 shadow-2xl relative border border-white/10 dark:border-slate-700">
            <button onclick="tutupModalSensus()" class="absolute top-6 right-6 w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500"><span class="material-symbols-outlined">close</span></button>
            <div class="mb-8 flex flex-col items-center text-center">
                <div class="w-16 h-16 bg-primary/10 dark:bg-indigo-500/20 text-primary dark:text-indigo-400 flex items-center justify-center rounded-3xl mb-4"><span class="material-symbols-outlined text-3xl">fact_check</span></div>
                <h3 class="font-headline font-black text-2xl italic uppercase text-on-surface dark:text-white">Laporan <span class="text-primary dark:text-indigo-400 italic">Kondisi</span></h3>
                <p class="text-xs text-on-surface-variant dark:text-slate-400 mt-2" id="task_item_name"></p>
            </div>
            <form action="<?php echo htmlspecialchars(dirname($_SERVER['PHP_SELF']) . '/../config/proses_sensus.php'); ?>" method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="task_id" id="task_id">
                <input type="hidden" name="action" value="submit_report">
                <div>
                    <label class="block text-[10px] font-black text-on-surface-variant dark:text-slate-400 uppercase tracking-widest mb-3 ml-2 text-center">Persentase Kelayakan (%)</label>
                    <div class="relative"><input type="number" name="condition_pct" min="0" max="100" value="100" required class="w-full text-center py-6 bg-surface-container-low dark:bg-slate-800 border-0 rounded-3xl focus:ring-4 focus:ring-primary/10 dark:focus:ring-indigo-500/20 outline-none font-black text-4xl text-on-surface dark:text-white"></div>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-on-surface-variant dark:text-slate-400 uppercase tracking-widest mb-3 ml-2">Catatan Kerusakan / Kendala</label>
                    <textarea name="notes" placeholder="Tuliskan kendala jika ada..." rows="3" class="w-full p-5 bg-surface-container-low dark:bg-slate-800 border-0 rounded-3xl focus:ring-4 focus:ring-primary/10 dark:focus:ring-indigo-500/20 outline-none font-medium text-sm text-on-surface dark:text-white dark:placeholder:opacity-50"></textarea>
                </div>
                <button type="submit" class="w-full bg-primary dark:bg-indigo-600 text-white font-black py-5 rounded-3xl shadow-xl shadow-primary/30 dark:shadow-indigo-900/50 hover:shadow-primary/50 dark:hover:shadow-indigo-900/80 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-[0.2em] text-xs">Kirim Laporan Sensus</button>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/bottom_nav_user.php'; ?>
    <script>
        function bukaModalSensus(id, name) {
            document.getElementById('task_id').value = id;
            document.getElementById('task_item_name').innerText = "Asset: " + name;
            const el = document.getElementById('modalSensus');
            el.classList.replace('hidden', 'flex');
        }
        function tutupModalSensus() {
            const el = document.getElementById('modalSensus');
            el.classList.replace('flex', 'hidden');
        }
    </script>
</body>
</html>
