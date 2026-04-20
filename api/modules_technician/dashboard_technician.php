<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

// Proteksi Teknisi
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'technician' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login_user.php");
    exit();
}

$tech_id = $_SESSION['user_id'];
$search_q = isset($_GET['q']) ? $_GET['q'] : '';

// Ambil Statistik Khusus Teknisi Ini
$stat_total = 0;
$stat_pending = 0;
$stat_process = 0;
$stat_done = 0;

$my_submissions = [];

if ($db) {
    try {
        $submissionsRef = $db->collection('submissions')->where('pic_id', '=', $tech_id);
        $docs = $submissionsRef->documents();
        
        foreach ($docs as $doc) {
            $row = $doc->data();
            $row['id'] = $doc->id();
            
            // Client-side search filtering
            if (!empty($search_q)) {
                $match = stripos($row['ticket_number'] ?? '', $search_q) !== false || 
                         stripos($row['title'] ?? '', $search_q) !== false;
                if (!$match) continue;
            }

            // Count stats
            $stat_total++;
            if ($row['status'] === 'Menunggu') $stat_pending++;
            elseif ($row['status'] === 'Proses') $stat_process++;
            elseif ($row['status'] === 'Selesai') $stat_done++;

            $my_submissions[] = $row;
        }

        // Sort by created_at DESC
        usort($my_submissions, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $tech_id_sql = mysqli_real_escape_string($conn, $tech_id);
    $sql = "SELECT s.*, u.full_name as user_name, u.department 
            FROM submissions s 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE s.pic_id = '$tech_id_sql'";
    
    if (!empty($search_q)) {
        $q = mysqli_real_escape_string($conn, $search_q);
        $sql .= " AND (s.ticket_number LIKE '%$q%' OR u.full_name LIKE '%$q%' OR s.title LIKE '%$q%')";
    }
    
    $sql .= " ORDER BY s.created_at DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $my_submissions[] = $row;
            $stat_total++;
            if ($row['status'] === 'Menunggu') $stat_pending++;
            elseif ($row['status'] === 'Proses') $stat_process++;
            elseif ($row['status'] === 'Selesai') $stat_done++;
        }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Technician Dashboard</title>
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
                        "primary": "#3525cd",
                        "primary-container": "#4f46e5",
                        "surface": "#f7f9fb",
                        "surface-container-low": "#f2f4f6",
                        "surface-container-lowest": "#ffffff",
                        "outline-variant": "#c7c4d8",
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
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen tech-layout">

    <?php include __DIR__ . '/../includes/navbar_technician.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <!-- Header Bar -->
        <header class="flex flex-col md:flex-row md:items-center gap-3 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-white/80 backdrop-blur-xl z-40">
            <div class="flex-1">
                <h1 class="font-headline text-2xl font-black text-on-surface tracking-tight italic uppercase">Technician <span class="text-primary italic">Dashboard</span></h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest leading-none mt-1">Mengelola Tugas Lapangan & Servis</p>
            </div>
            <form action="" method="GET" class="relative group w-full md:w-64">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
                <input name="q" value="<?php echo htmlspecialchars($search_q); ?>" type="text" placeholder="Cari tiket..." class="pl-10 pr-4 py-2 bg-surface-container-low border-0 rounded-xl text-xs font-bold text-on-surface outline-none focus:ring-2 focus:ring-primary/20 transition-all w-full">
            </form>
        </header>

        <div class="p-4 md:p-8 space-y-6 md:space-y-8">
            <!-- Stats Grid -->
            <section class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-outline-variant/5">
                    <p class="text-[10px] font-black text-outline uppercase tracking-widest mb-1">Total Assigned</p>
                    <h4 class="text-3xl font-black text-on-surface"><?php echo $stat_total; ?></h4>
                </div>
                <div class="bg-orange-50 p-6 rounded-3xl shadow-sm border border-orange-100">
                    <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest mb-1">Pending</p>
                    <h4 class="text-3xl font-black text-orange-600"><?php echo $stat_pending; ?></h4>
                </div>
                <div class="bg-indigo-50 p-6 rounded-3xl shadow-sm border border-indigo-100">
                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">On Process</p>
                    <h4 class="text-3xl font-black text-indigo-600"><?php echo $stat_process; ?></h4>
                </div>
                <div class="bg-emerald-50 p-6 rounded-3xl shadow-sm border border-emerald-100 text-emerald-600">
                    <p class="text-[10px] font-black text-emerald-400 uppercase tracking-widest mb-1">Completed</p>
                    <h4 class="text-3xl font-black text-emerald-600"><?php echo $stat_done; ?></h4>
                </div>
            </section>

            <!-- Tasks Table -->
            <section class="bg-white rounded-[2.5rem] border border-outline-variant/10 shadow-2xl shadow-indigo-900/5 overflow-hidden">
                <div class="p-6 md:p-8 border-b border-outline-variant/5 flex items-center justify-between">
                    <h2 class="font-headline text-lg font-black text-on-surface italic uppercase tracking-tighter">My <span class="text-primary italic">Active Assignments</span></h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/50 border-b border-outline-variant/10">
                                <th class="px-8 py-5">Ticket</th>
                                <th class="px-8 py-5">Type / Title</th>
                                <th class="px-8 py-5">Requestor</th>
                                <th class="px-8 py-5 text-center">Status</th>
                                <th class="px-8 py-5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/10">
                            <?php if (count($my_submissions) > 0): ?>
                                <?php foreach ($my_submissions as $row): ?>
                                <tr class="group hover:bg-surface-variant/10 transition-all">
                                    <td class="px-8 py-6">
                                        <span class="font-headline font-extrabold text-primary italic">#<?php echo htmlspecialchars($row['ticket_number']); ?></span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black text-indigo-400 uppercase italic leading-none mb-1"><?php echo htmlspecialchars($row['type']); ?></span>
                                            <span class="text-sm font-bold text-on-surface truncate w-48"><?php echo htmlspecialchars($row['title']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col text-xs">
                                            <span class="font-bold text-on-surface"><?php echo htmlspecialchars($row['user_name'] ?? 'User'); ?></span>
                                            <span class="text-outline uppercase tracking-tighter text-[10px] font-bold"><?php echo htmlspecialchars($row['department'] ?? '-'); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black border
                                            <?php 
                                                if($row['status'] == 'Selesai') echo 'bg-emerald-50 text-emerald-700 border-emerald-100';
                                                elseif($row['status'] == 'Proses') echo 'bg-indigo-50 text-indigo-700 border-indigo-100';
                                                elseif($row['status'] == 'Ditolak') echo 'bg-red-50 text-red-700 border-red-100';
                                                else echo 'bg-orange-50 text-orange-700 border-orange-100';
                                            ?>">
                                            <?php echo strtoupper($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <a href="<?php echo ($row['type'] == 'Maintenance' ? 'kelola_maintenance.php' : 'kelola_pengajuan.php'); ?>?id=<?php echo $row['id']; ?>" 
                                           class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2 rounded-xl text-[10px] font-black hover:bg-indigo-700 transition-all uppercase shadow-lg shadow-indigo-200">
                                           Handle Task
                                           <span class="material-symbols-outlined text-xs">arrow_forward_ios</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-8 py-20 text-center">
                                        <div class="opacity-30 grayscale flex flex-col items-center">
                                            <span class="material-symbols-outlined text-[64px] mb-2">task_alt</span>
                                            <p class="font-black text-xs uppercase tracking-widest">No active tasks assigned to you.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
