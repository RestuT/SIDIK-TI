<?php

require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../config/csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Ambil data pengajuan milik user dari Firestore
$submissions_docs = $db->collection('submissions')
    ->where('user_id', '=', $user_id)
    ->orderBy('created_at', 'DESC')
    ->documents();

// 2. Ambil data PIC (admin) untuk mapping nama
$users_docs = $db->collection('users')->where('role', '=', 'admin')->documents();
$pic_map = [];
foreach ($users_docs as $doc) {
    $u = $doc->data();
    $pic_map[$doc->id()] = $u['full_name'] ?? 'Admin';
}

$submission_list = [];
foreach ($submissions_docs as $doc) {
    $s = $doc->data();
    $s['id'] = $doc->id();
    $s['pic_name'] = $pic_map[$s['pic_id'] ?? ''] ?? null;
    $submission_list[] = $s;
}

$total_records = count($submission_list);
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Request Audit & History</title>
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
                        "primary": "#4f46e5", // Indigo 600
                        "primary-container": "#eef2ff",
                        "procurement": "#f59e0b", // Amber 500
                        "maintenance": "#10b981", // Emerald 500
                        "surface": "#f8fafc",
                        "on-surface": "#0f172a",
                        "on-surface-variant": "#64748b",
                        "outline-variant": "#e2e8f0",
                        "error": "#ef4444",
                    },
                    fontFamily: {
                        "headline": ["Plus Jakarta Sans"],
                        "body": ["Inter"],
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
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f8fafc; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        input:focus { outline: none; border: none; ring: 0; }
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>

    <main class="max-w-[1240px] mx-auto px-6 py-12">
        <!-- Modern Header Section -->
        <header class="mb-12">
            <div>
                <h1 class="font-headline text-3xl font-black text-on-surface tracking-tight leading-none uppercase italic underline decoration-primary/30 underline-offset-8">Audit <span class="text-primary italic">& History</span></h1>
                <p class="text-[10px] text-on-surface-variant font-bold uppercase tracking-[0.2em] mt-3">Lacak Status & Rekam Jejak Pengajuan Digital Anda</p>
            </div>
            <div class="flex items-center gap-4 mt-6">
                <div class="bg-primary/5 px-6 py-3 rounded-2xl border border-primary/10 flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-xl">history</span>
                    <span class="text-xs font-black text-primary uppercase tracking-widest leading-none"><?php echo $total_records; ?> Total Records</span>
                </div>
            </div>
        </header>

        <div class="p-4 md:p-10 max-w-[1400px] mx-auto w-full">
            <!-- Table Container -->
        <div class="bg-white rounded-[2.5rem] border border-outline-variant shadow-xl shadow-slate-200/50 overflow-hidden relative group">
            <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-primary to-primary-container"></div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Referensi</th>
                            <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Kategori & Subjek</th>
                            <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Update Status</th>
                            <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] text-center">PIC IT</th>
                            <th class="px-8 py-5 text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php if($total_records > 0): ?>
                            <?php foreach($submission_list as $row): ?>
                            <tr class="group/row hover:bg-slate-50/50 transition-all">
                                <!-- Ticket & Date -->
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="font-black text-primary italic text-sm tracking-tight">#<?php echo htmlspecialchars($row['ticket_number'] ?? ''); ?></span>
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1"><?php echo isset($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '-'; ?></span>
                                    </div>
                                </td>

                                <!-- Service & Subject -->
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <div class="flex items-center gap-2 mb-1.5">
                                            <span class="material-symbols-outlined text-[14px] <?php echo ($row['type'] ?? '') == 'Maintenance' ? 'text-maintenance' : 'text-procurement'; ?>">
                                                <?php echo ($row['type'] ?? '') == 'Maintenance' ? 'build' : 'shopping_bag'; ?>
                                            </span>
                                            <span class="text-[9px] font-black uppercase tracking-tight <?php echo ($row['type'] ?? '') == 'Maintenance' ? 'text-emerald-700' : 'text-amber-700'; ?>">
                                                <?php echo htmlspecialchars($row['type'] ?? ''); ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-on-surface font-bold tracking-tight group-hover/row:text-primary transition-colors"><?php echo htmlspecialchars($row['title'] ?? ''); ?></p>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="px-8 py-6">
                                    <div class="flex flex-col gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border w-fit
                                            <?php 
                                                $s = $row['status'] ?? '';
                                                if($s == 'Selesai') echo 'bg-emerald-50 text-maintenance border-emerald-100';
                                                elseif($s == 'Proses') echo 'bg-indigo-50 text-indigo-700 border-indigo-100';
                                                elseif($s == 'Ditolak') echo 'bg-error/5 text-error border-error-100';
                                                else echo 'bg-amber-50 text-amber-700 border-amber-100';
                                            ?>">
                                            <?php echo htmlspecialchars($s); ?>
                                        </span>
                                        <?php if(($row['is_appealed'] ?? 0) == 1): ?>
                                            <div class="flex items-center gap-1 text-[8px] font-bold text-error italic uppercase">
                                                <span class="material-symbols-outlined text-[10px] animate-bounce">priority_high</span>
                                                Appeal History Active
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- PIC Agent -->
                                <td class="px-8 py-6 text-center">
                                    <?php if($row['pic_name']): ?>
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-100 group-hover/row:border-primary/20 transition-all">
                                            <span class="text-[10px] font-bold text-on-surface"><?php echo htmlspecialchars($row['pic_name']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-[9px] font-black text-slate-300 uppercase italic">Awaiting PIC</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="px-8 py-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="<?php echo ($row['type'] ?? '') == 'Maintenance' ? 'cetak_tiket_maintenance.php' : 'cetak_tiket_pengadaan.php'; ?>?id=<?php echo $row['id']; ?>" target="_blank"
                                           class="w-8 h-8 rounded-lg bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400 hover:text-primary hover:bg-primary/5 transition-all" title="Print">
                                            <span class="material-symbols-outlined text-base">print</span>
                                        </a>
                                        
                                        <?php if(($row['status'] ?? '') == 'Ditolak' && ($row['is_appealed'] ?? 0) == 0): ?>
                                            <button onclick='openAppealModal(<?php echo json_encode($row); ?>)'
                                                class="h-10 px-5 rounded-xl bg-error text-white text-[10px] font-black uppercase tracking-widest hover:shadow-lg hover:shadow-error/30 hover:-translate-y-0.5 transition-all">
                                                Sanggah
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-10 py-32 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="material-symbols-outlined text-6xl text-slate-100">receipt_long</span>
                                        <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest mt-6">Records are currently empty</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </main>

    <!-- Modal Appeal -->
    <div id="modalAppeal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] items-center justify-center p-6">
        <div class="bg-white w-full max-w-lg rounded-[3rem] shadow-2xl animate-in zoom-in duration-300 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-error to-amber-500"></div>
            
            <form action="../config/proses_sanggahan.php" method="POST" class="p-10 space-y-8">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <input type="hidden" name="submission_id" id="appeal_id">
                
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-error/10 flex items-center justify-center text-error">
                        <span class="material-symbols-outlined text-3xl">emergency_home</span>
                    </div>
                    <div>
                        <h2 class="font-headline text-2xl font-black text-on-surface uppercase tracking-tight">Formulir <span class="text-error">Sanggahan</span></h2>
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Digital Dispute Resolution Flow</p>
                    </div>
                </div>

                <div class="bg-slate-50 p-6 rounded-3xl border border-slate-100 flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm text-error">info</span>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Alasan Penolakan Admin:</p>
                    </div>
                    <p id="reject_reason" class="text-sm font-bold text-on-surface italic leading-relaxed"></p>
                </div>

                <div class="space-y-3">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Alasan Sanggahan Anda</label>
                    <textarea name="appeal_reason" required rows="4" 
                        class="w-full p-6 bg-slate-50 border-0 rounded-3xl outline-none focus:ring-4 focus:ring-primary/10 transition-all text-sm font-bold text-on-surface-variant placeholder:opacity-30"
                        placeholder="Berikan argumentasi atau penjelasan tambahan mengapa pengajuan ini tetap diperlukan..."></textarea>
                </div>

                <div class="flex gap-4">
                    <button type="button" onclick="closeModal()" class="flex-1 h-16 bg-slate-50 text-slate-400 font-black rounded-2xl hover:bg-slate-100 transition-all text-[10px] uppercase tracking-widest">Batalkan</button>
                    <button type="submit" class="flex-[2] h-16 bg-error text-white font-black rounded-2xl shadow-xl shadow-error/20 hover:shadow-error/40 hover:-translate-y-1 transition-all text-[10px] uppercase tracking-widest flex items-center justify-center gap-3">
                        <span class="material-symbols-outlined text-base">send</span>
                        Kirim Sanggahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openAppealModal(data) {
        document.getElementById('appeal_id').value = data.id;
        document.getElementById('reject_reason').innerText = data.admin_reasoning || "No system justification provided by technical administrator.";
        
        const modal = document.getElementById('modalAppeal');
        modal.classList.replace('hidden', 'flex');
    }

    function closeModal() {
        const modal = document.getElementById('modalAppeal');
        modal.classList.replace('flex', 'hidden');
    }

    // Backdrop click close
    document.getElementById('modalAppeal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    </script>
    <?php include __DIR__ . '/../includes/bottom_nav_user.php'; ?>
</body>
</html>
