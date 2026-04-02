<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi akses untuk role admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

$search_q = isset($_GET['q']) ? $_GET['q'] : '';

// Fetch all users with role 'user'
$usersRef = $db->collection('users')->where('role', '=', 'user');
$users_docs = $usersRef->documents();

$user_list = [];
foreach ($users_docs as $doc) {
    $u = $doc->data();
    $u['id'] = $doc->id();
    
    // Client-side filtering
    if (!empty($search_q)) {
        $match = stripos($u['full_name'] ?? '', $search_q) !== false || 
                 stripos($u['department'] ?? '', $search_q) !== false || 
                 stripos($u['jabatan'] ?? '', $search_q) !== false;
        if (!$match) continue;
    }
    
    $user_list[] = $u;
}

// Sort by full_name ASC
usort($user_list, function($a, $b) {
    return strcmp($a['full_name'] ?? '', $b['full_name'] ?? '');
});

$active_count = count($user_list);
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | User Directory Management</title>
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
                <h1 class="font-headline text-2xl font-extrabold text-on-surface tracking-tight leading-none italic uppercase">User <span class="text-primary italic">Directory</span></h1>
                <p class="text-[10px] text-outline font-black uppercase tracking-widest mt-1">Manajemen Akses & Personalia Karyawan</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="bg-primary-fixed/20 px-4 py-2 rounded-2xl border border-primary-fixed/30 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-sm fill-1">verified_user</span>
                    <span class="text-[10px] font-black text-primary uppercase tracking-widest"><?php echo $active_count; ?> Account Active</span>
                </div>
            </div>
        </header>

        <div class="p-8 space-y-8">
            <!-- Alert Messages -->
            <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                <div class="bg-primary-fixed/10 text-primary p-4 rounded-2xl flex items-center gap-3 border border-primary-fixed/30 animate-in fade-in slide-in-from-top-2 duration-300 font-bold uppercase tracking-widest text-xs">
                    <span class="material-symbols-outlined fill-1">info</span>
                    Data personil telah berhasil dieliminasi dari database sistem.
                </div>
            <?php endif; ?>

            <!-- Directory Table Panel -->
            <section class="bg-white rounded-[2.5rem] border border-outline-variant/10 shadow-2xl shadow-indigo-900/5 overflow-hidden">
                <div class="p-8 border-b border-outline-variant/5 flex items-center justify-between">
                    <h2 class="font-headline text-xl font-black text-on-surface italic uppercase tracking-tighter">Registered <span class="text-primary italic">Personalities</span></h2>
                    
                    <form action="" method="GET" class="relative group">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-lg">search</span>
                        <input name="q" value="<?php echo htmlspecialchars($search_q); ?>" type="text" placeholder="Temukan nama..." class="pl-10 pr-10 py-2 bg-surface-container-low dark:bg-slate-800 border-0 rounded-xl text-xs font-bold text-on-surface dark:text-white outline-none focus:ring-2 focus:ring-primary/20 transition-all w-64">
                        <?php if(!empty($search_q)): ?>
                            <a href="manage_users.php" class="absolute right-3 top-1/2 -translate-y-1/2 text-outline hover:text-rose-500 transition-colors">
                                <span class="material-symbols-outlined text-sm">close</span>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-container-low/30 border-b border-outline-variant/10">
                                <th class="px-8 py-5 w-20 text-center">Rank</th>
                                <th class="px-8 py-5">Profile Information</th>
                                <th class="px-8 py-5">Position & Office</th>
                                <th class="px-8 py-5">Authentication Identity</th>
                                <th class="px-8 py-5 text-center">Operation</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/5">
                            <?php if(count($user_list) > 0): $no = 1; ?>
                                <?php foreach($user_list as $row): ?>
                                <tr class="group hover:bg-surface-variant/5 transition-all">
                                    <td class="px-8 py-6 text-center">
                                        <span class="text-xs font-black text-outline group-hover:text-primary transition-colors italic">#<?php echo sprintf("%02d", $no++); ?></span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-xl bg-surface-container-high flex items-center justify-center text-primary font-black uppercase text-sm border border-outline-variant/20 group-hover:bg-primary-container group-hover:text-white transition-all">
                                                <?php echo substr($row['full_name'] ?? 'U', 0, 1); ?>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="font-headline font-bold text-on-surface group-hover:text-primary transition-colors leading-none"><?php echo htmlspecialchars($row['full_name'] ?? '-'); ?></span>
                                                <span class="text-[9px] text-outline font-black uppercase tracking-widest mt-1">ID: <?php echo substr($row['id'], 0, 8); ?>...</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-1.5 mb-1">
                                                <span class="material-symbols-outlined text-sm text-primary">apartment</span>
                                                <span class="text-[11px] font-black text-on-surface uppercase italic"><?php echo htmlspecialchars($row['department'] ?? 'UNSET'); ?></span>
                                            </div>
                                            <span class="text-[10px] text-outline font-bold leading-none ml-5"><?php echo htmlspecialchars($row['jabatan'] ?? 'Position Not Set'); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-surface-container-low border border-outline-variant/10 group-hover:border-primary/20 transition-all">
                                            <span class="material-symbols-outlined text-primary text-sm">key</span>
                                            <span class="text-[10px] font-black text-on-surface-variant font-mono"><?php echo htmlspecialchars($row['username'] ?? ''); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-center">
                                        <a href="../config/hapus_user.php?id=<?php echo $row['id']; ?>" 
                                            onclick="return confirm('PERINGATAN!\n\nMenghapus akun <?php echo htmlspecialchars(addslashes($row['username'] ?? '')); ?> akan mempengaruhi tiket history miliknya (jika ada).\n\nYakin ingin menghapus permanen karyawan ini?');" 
                                            class="w-10 h-10 inline-flex items-center justify-center bg-error/5 text-error rounded-xl hover:bg-error hover:text-white transition-all shadow-sm border border-error/10 hover:shadow-error/20 active:scale-90 group/del">
                                            <span class="material-symbols-outlined text-xl group-hover/del:fill-1">person_remove</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center justify-center opacity-40 grayscale group hover:grayscale-0 transition-all">
                                            <span class="material-symbols-outlined text-[64px] mb-4 text-outline">no_accounts</span>
                                            <p class="font-headline font-black text-on-surface uppercase tracking-widest text-xs">Zero Personalities Detected</p>
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
