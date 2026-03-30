<?php
session_start();
include '../config/database.php';
include '../config/csrf_helper.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Ambil Statistik Aset User
$stat_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM asset_assignments WHERE user_id = '$user_id'"))['total'];
$stat_active = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM asset_assignments WHERE user_id = '$user_id' AND status = 'Active'"))['total'];
$stat_maintenance = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM asset_assignments WHERE user_id = '$user_id' AND status = 'Maintenance'"))['total'];

// 2. Ambil Daftar Aset
$query = "SELECT * FROM asset_assignments WHERE user_id = '$user_id' ORDER BY assigned_at DESC";
$result = mysqli_query($conn, $query);

// 3. User Detail (untuk Header)
$user_meta = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM users WHERE id = '$user_id'"));
$display_name = $user_meta['full_name'];
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | My Assets</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Inter:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
          tailwind.config = {
            darkMode: "class",
            theme: {
              extend: {
                colors: {
                  "primary": "#3525cd",
                  "primary-container": "#4f46e5",
                  "background": "#f7f9fb",
                  "on-surface": "#191c1e",
                  "on-surface-variant": "#464555",
                  "surface-container-lowest": "#ffffff",
                  "surface-container-low": "#f2f4f6",
                  "surface-container-high": "#e6e8ea",
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
    <style>
            .material-symbols-outlined {
                font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            }
            body { font-family: 'Inter', sans-serif; }
            h1, h2, h3, .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
        </style>
</head>
<body class="bg-background text-on-surface min-h-screen selection:bg-primary/20">
    <?php include '../includes/navbar_user.php'; ?>

    <main class="max-w-7xl mx-auto px-6 md:px-10 py-10 space-y-10">
        <!-- Header -->
        <section class="space-y-1">
            <p class="text-on-surface-variant font-medium tracking-wide text-xs uppercase">Inventory</p>
            <h1 class="text-4xl font-extrabold text-on-surface tracking-tight">Aset Saya</h1>
            <p class="text-on-surface-variant max-w-lg">Daftar perangkat IT yang saat ini berada di bawah tanggung jawab Anda.</p>
        </section>

        <!-- Stats Grid -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
             <div class="bg-surface-container-lowest p-8 rounded-3xl shadow-sm border border-transparent hover:border-primary/10 transition-all duration-500 group">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-indigo-50 text-indigo-600 rounded-2xl">
                        <span class="material-symbols-outlined text-3xl">inventory_2</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <h3 class="text-4xl font-black text-on-surface"><?php echo $stat_total; ?></h3>
                    <p class="text-on-surface-variant font-medium">Total Perangkat</p>
                </div>
            </div>
            <div class="bg-surface-container-lowest p-8 rounded-3xl shadow-sm border border-transparent hover:border-primary/10 transition-all duration-500 group">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-emerald-50 text-emerald-600 rounded-2xl">
                        <span class="material-symbols-outlined text-3xl">check_circle</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <h3 class="text-4xl font-black text-on-surface"><?php echo $stat_active; ?></h3>
                    <p class="text-on-surface-variant font-medium">Kondisi Baik</p>
                </div>
            </div>
            <div class="bg-surface-container-lowest p-8 rounded-3xl shadow-sm border border-transparent hover:border-primary/10 transition-all duration-500 group">
                <div class="flex justify-between items-start mb-6">
                    <div class="p-4 bg-orange-50 text-orange-600 rounded-2xl">
                        <span class="material-symbols-outlined text-3xl">build</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <h3 class="text-4xl font-black text-on-surface"><?php echo $stat_maintenance; ?></h3>
                    <p class="text-on-surface-variant font-medium">Perlu Perbaikan</p>
                </div>
            </div>
        </section>

        <!-- Asset List -->
        <section class="bg-surface-container-lowest rounded-3xl p-8 shadow-sm border border-outline-variant/10">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-on-surface-variant text-xs uppercase tracking-[0.15em] border-b border-slate-100">
                            <th class="px-6 py-5 font-bold">Perangkat</th>
                            <th class="px-6 py-5 font-bold">Serial Number</th>
                            <th class="px-6 py-5 font-bold">Kategori</th>
                            <th class="px-6 py-5 font-bold">Tanggal Terima</th>
                            <th class="px-6 py-5 font-bold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if($stat_total > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-6 transition-all duration-300">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform">
                                            <span class="material-symbols-outlined">
                                                <?php 
                                                    if($row['category'] == 'Laptop') echo 'laptop_mac';
                                                    elseif($row['category'] == 'Monitor') echo 'monitor';
                                                    elseif($row['category'] == 'Printer') echo 'print';
                                                    else echo 'devices';
                                                ?>
                                            </span>
                                        </div>
                                        <span class="font-bold text-on-surface"><?php echo htmlspecialchars($row['item_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-6 text-on-surface-variant font-mono text-sm"><?php echo htmlspecialchars($row['serial_number']); ?></td>
                                <td class="px-6 py-6 text-on-surface-variant"><?php echo htmlspecialchars($row['category']); ?></td>
                                <td class="px-6 py-6 text-on-surface-variant"><?php echo date('d M Y', strtotime($row['assigned_at'])); ?></td>
                                <td class="px-6 py-6">
                                    <?php 
                                        $statusClass = "bg-emerald-50 text-emerald-700 border-emerald-100";
                                        if($row['status'] == 'Maintenance') $statusClass = "bg-orange-50 text-orange-700 border-orange-100";
                                        elseif($row['status'] == 'Returned') $statusClass = "bg-slate-50 text-slate-700 border-slate-100";
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border <?php echo $statusClass; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center gap-3 text-on-surface-variant opacity-40">
                                        <span class="material-symbols-outlined text-6xl">inventory_2</span>
                                        <p class="font-bold">Belum ada aset yang tercatat atas nama Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- BottomNavBar for Mobile -->
    <nav class="md:hidden fixed bottom-0 w-full z-50 flex justify-around items-center px-6 py-3 pb-safe bg-white/80 dark:bg-slate-900/80 backdrop-blur-lg shadow-xl rounded-t-3xl border-t border-indigo-50">
        <a class="flex flex-col items-center justify-center text-slate-400 px-5 py-2" href="dashboard_user.php">
            <span class="material-symbols-outlined">grid_view</span>
            <span class="text-[10px] font-bold uppercase tracking-wider mt-1">Home</span>
        </a>
        <a class="flex flex-col items-center justify-center text-slate-400 px-5 py-2" href="dashboard_audit.php">
            <span class="material-symbols-outlined">handyman</span>
            <span class="text-[10px] font-bold uppercase tracking-wider mt-1">Requests</span>
        </a>
        <a class="flex flex-col items-center justify-center bg-indigo-50 text-indigo-700 rounded-2xl px-5 py-2" href="assets_user.php">
            <span class="material-symbols-outlined">list_alt</span>
            <span class="text-[10px] font-bold uppercase tracking-wider mt-1">Assets</span>
        </a>
        <a class="flex flex-col items-center justify-center text-slate-400 px-5 py-2" href="#">
            <span class="material-symbols-outlined">person</span>
            <span class="text-[10px] font-bold uppercase tracking-wider mt-1">Profile</span>
        </a>
    </nav>
</body>
</html>
