<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/pagination_helper.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

/**
 * UTILITY: Deep Scan Firestore Collections
 */
function getCollectionSummary($db) {
    $summary = [];
    try {
        // Pada Firestore PHP Admin SDK, collections() mengembalikan array of CollectionReference
        $collections = $db->collections();
        foreach ($collections as $col) {
            $id = $col->id();
            $count = $col->count(); // Optimized count() call
            $summary[$id] = [
                'id' => $id,
                'count' => $count,
                'path' => $col->path()
            ];
        }
    } catch (Exception $e) {
        $summary['error'] = $e->getMessage();
    }
    return $summary;
}

$collections_summary = getCollectionSummary($db);

// Additional System Aggregations
$total_assets_value = 0;
$total_purchase_value = 0;
try {
    $assignments = $db->collection('asset_assignments')->documents();
    foreach ($assignments as $doc) {
        $data = $doc->data();
        $total_purchase_value += (float)($data['purchase_price'] ?? 0);
        // Simplified current value for summary
        $total_assets_value += (float)($data['current_value'] ?? ($data['purchase_price'] ?? 0));
    }
} catch (Exception $e) { }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Database Explorer';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
    <style>
        .obsidian-card {
            background-color: #0a0a0a;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 2rem;
        }
        @keyframes subtle-glow {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        .glow-dot { animation: subtle-glow 3s infinite; }
    </style>
</head>
<body class="selection:bg-primary/40 pb-24 lg:pb-0">
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <!-- Main Content -->
    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen">
        
        <div class="max-w-7xl mx-auto p-6 lg:p-12 space-y-12">
            
            <!-- Header -->
            <header class="flex flex-col md:flex-row md:items-end justify-between gap-10">
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-highlight-indigo text-primary rounded-full text-[10px] font-black uppercase tracking-widest border border-primary/10">
                        <span class="material-symbols-outlined text-[14px]">terminal</span>
                        Full System Scan
                    </div>
                    <h1 class="text-5xl font-extrabold text-on-surface tracking-tighter italic uppercase">Database <span class="text-primary italic">Deep Scan</span></h1>
                    <p class="text-on-surface-variant max-w-lg font-medium text-sm leading-relaxed">Audit menyeluruh terhadap seluruh koleksi dan data dalam infrastruktur Cloud Firestore.</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3 px-5 py-3 rounded-2xl obsidian-card bg-surface-low border-none">
                        <div class="w-2.5 h-2.5 rounded-full bg-primary glow-dot"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-on-surface">Data Synced Live</span>
                    </div>
                </div>
            </header>

            <!-- Major Stats -->
            <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="p-8 obsidian-card hover:scale-[1.02] transition-transform">
                    <h4 class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] mb-4">Total Purchase Value</h4>
                    <p class="text-3xl font-black text-on-surface tracking-tighter leading-none italic">Rp <?php echo number_format($total_purchase_value, 0, ',', '.'); ?></p>
                </div>
                <div class="p-8 obsidian-card border-primary/20 hover:scale-[1.02] transition-transform">
                    <h4 class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] mb-4 text-primary">Total Current Val</h4>
                    <p class="text-3xl font-black text-primary tracking-tighter leading-none italic">Rp <?php echo number_format($total_assets_value, 0, ',', '.'); ?></p>
                </div>
                <div class="p-8 obsidian-card hover:scale-[1.02] transition-transform">
                    <h4 class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] mb-4">Total Collections</h4>
                    <p class="text-3xl font-black text-on-surface tracking-tighter leading-none italic"><?php echo count($collections_summary); ?></p>
                </div>
                <div class="p-8 obsidian-card hover:scale-[1.02] transition-transform">
                    <h4 class="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] mb-4">Status</h4>
                    <p class="text-3xl font-black text-emerald-500 tracking-tighter leading-none italic">CONNECTED</p>
                </div>
            </section>

            <!-- Collection List -->
            <section class="space-y-6">
                <div class="flex items-center justify-between px-2">
                    <h2 class="font-headline text-xl font-black text-on-surface italic uppercase tracking-tighter">Collection <span class="text-primary italic">Inventory</span></h2>
                </div>
                
                <div class="obsidian-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-on-surface-variant text-[10px] font-black uppercase tracking-[0.2em] bg-surface-low border-b border-white/5">
                                    <th class="px-8 py-6">Collection ID</th>
                                    <th class="px-8 py-6">Document Count</th>
                                    <th class="px-8 py-6">Storage Path</th>
                                    <th class="px-8 py-6 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach($collections_summary as $col): 
                                    if(isset($col['id'])):
                                ?>
                                <tr class="group hover:bg-white/5 transition-colors">
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                                                <span class="material-symbols-outlined text-base">folder_open</span>
                                            </div>
                                            <span class="font-bold text-on-surface"><?php echo $col['id']; ?></span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="font-mono text-xs text-on-surface-variant bg-white/5 px-2 py-1 rounded-md">
                                            <?php echo number_format($col['count']); ?> docs
                                        </span>
                                    </td>
                                    <td class="px-8 py-6">
                                        <span class="text-[10px] font-mono text-outline uppercase"><?php echo $col['path']; ?></span>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <a href="?inspect=<?php echo urlencode($col['id']); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-highlight-indigo text-primary rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-primary hover:text-white transition-all">
                                            Inspect Data
                                            <span class="material-symbols-outlined text-xs">zoom_in</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Data Inspector Panel (Conditional) -->
            <?php if(isset($_GET['inspect'])): 
                $target = $_GET['inspect'];
                $peekDocs = $db->collection($target)->limit(10)->documents();
            ?>
            <section class="space-y-6 animate-fade-in">
                <div class="flex items-center justify-between px-2">
                    <h2 class="font-headline text-xl font-black text-on-surface italic uppercase tracking-tighter">Peeking: <span class="text-primary italic"><?php echo $target; ?></span></h2>
                    <a href="db_audit_all.php" class="text-[10px] font-black text-on-surface-variant hover:text-rose-500 uppercase tracking-widest transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">close</span>
                        Close Preview
                    </a>
                </div>
                
                <div class="obsidian-card p-8 bg-surface-low space-y-4">
                    <?php foreach($peekDocs as $d): if($d->exists()): ?>
                        <div class="p-4 rounded-2xl bg-black/40 border border-white/5 space-y-2">
                            <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest text-primary">
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[12px]">description</span> Doc ID: <?php echo $d->id(); ?></span>
                                <span class="text-on-surface-variant">Peek Mode</span>
                            </div>
                            <pre class="text-[10px] font-mono text-on-surface-variant overflow-x-auto p-2 bg-black/20 rounded-lg"><?php echo json_encode($d->data(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></pre>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </div>
    </main>

    <?php include __DIR__ . '/../includes/bottom_nav_admin.php'; ?>

</body>
</html>
