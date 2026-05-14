<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

// Proteksi halaman
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user detail for Header
$display_name = 'User';
if ($db) {
    try {
        $userSnap = $db->collection('users')->document($user_id)->snapshot();
        $display_name = $userSnap->exists() ? ($userSnap->get('full_name') ?? 'User') : 'User';
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $uid_e = mysqli_real_escape_string($conn, $user_id);
    $res = mysqli_query($conn, "SELECT full_name FROM users WHERE id = '$uid_e' LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) {
        $display_name = $row['full_name'];
    }
}

// Data statis FAQ
$faqs = [
    [
        "category" => "Jaringan & Konektivitas",
        "icon" => "wifi",
        "items" => [
            [
                "q" => "Bagaimana cara menyambung ke VPN Kantor dari rumah?",
                "a" => "Pastikan Anda telah menginstal aplikasi OpenVPN Connect. Unduh profil konfigurasi dari portal intranet, masukkan kredensial akun AD Anda, dan klik tombol 'Connect'. Jika muncul error sertifikat, mintalah pembaruan ke tim IT."
            ],
            [
                "q" => "Internet lambat atau terputus secara berkala?",
                "a" => "Cobalah matikan terlebih dahulu koneksi Wi-Fi Anda selama 10 detik lalu sambungkan kembali. Hindari menggunakan koneksi untuk streaming video resolusi tinggi saat sedang beroperasi pada aplikasi internal berat."
            ]
        ]
    ],
    [
        "category" => "Perangkat Keras (Hardware)",
        "icon" => "devices",
        "items" => [
            [
                "q" => "Printer menampilkan status 'Offline' dan tidak mau mencetak",
                "a" => "Periksa apakah lampu indikator pada printer menyala (tidak berkedip merah). Pastikan kabel USB/LAN terpasang solid. Jika menggunakan printer nirkabel (Wireless), restart printer dan tunggu 2 menit sebelum mencoba mencetak kembali."
            ],
            [
                "q" => "Laptop mengalami Blue Screen atau Restart Sendiri",
                "a" => "Segera simpan pekerjaan Anda jika sempat. Hentikan pemakaian paksa dan catat 'Stop Code' yang tertera pada layar biru. Segera laporkan melalui fitur form Maintenance agar tim IT dapat melakukan diagnosa mendalam."
            ]
        ]
    ],
    [
        "category" => "Keamanan Siber & Akun",
        "icon" => "security",
        "items" => [
            [
                "q" => "Saya lupa kata sandi akun portal SIDIK-TI saya.",
                "a" => "Saat ini Reset Password mandiri hanya bisa dilakukan melalui halaman Profil jika Anda masih memiliki akses login. Jika benar-benar terkunci, silakan mendatangi langsung Divisi IT dengan membawa ID Card Anda."
            ],
            [
                "q" => "Saya menerima email mencurigakan (Potensi Phishing).",
                "a" => "JANGAN MENGKLIK TAUTAN APAPUN dari email tersebut. Jangan pula membalasnya. Teruskan email tersebut ke alamat tim keamanan IT, atau laporkan melalui fitur pengaduan IT sesegera mungkin."
            ]
        ]
    ],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Knowledge Base';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-background dark:bg-slate-950 text-on-surface dark:text-slate-100 min-h-screen selection:bg-primary/20 pb-24 md:pb-0 transition-colors duration-300">
    
    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>

    <!-- Hero Section -->
    <div class="relative pt-20 pb-16 px-6 lg:px-8 overflow-hidden bg-white dark:bg-slate-900 border-b border-indigo-50 dark:border-slate-800">
        <!-- Dekorasi Background -->
        <div class="absolute inset-y-0 right-1/2 -z-10 mr-16 w-[200%] origin-bottom-left skew-x-[-30deg] bg-white dark:bg-slate-900 shadow-xl shadow-indigo-600/10 dark:shadow-none ring-1 ring-indigo-50 dark:ring-slate-800 sm:mr-28 lg:mr-0 xl:mr-16 xl:origin-center"></div>
        <div class="absolute top-0 right-0 p-32 opacity-5 dark:opacity-10 mix-blend-multiply pointer-events-none -z-10">
            <span class="material-symbols-outlined text-[300px]">lightbulb</span>
        </div>

        <div class="max-w-7xl mx-auto text-center space-y-6">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 text-indigo-700 dark:text-indigo-400 font-bold text-xs uppercase tracking-widest mb-2 shadow-sm">
                <span class="material-symbols-outlined text-[14px]">library_books</span>
                Pusat Bantuan IT
            </div>
            <h1 class="text-4xl md:text-6xl font-black font-headline tracking-tight text-slate-900 dark:text-white italic">
                Knowledge <span class="text-primary dark:text-indigo-400 italic">Base</span>
            </h1>
            <p class="mt-4 text-base md:text-lg leading-8 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto font-medium">
                Temukan solusi cepat dan tutorial langkah demi langkah untuk setiap kendala perangkat dan konektivitas. Jadilah IT untuk diri Anda sendiri!
            </p>

            <!-- Search Bar Mockup -->
            <div class="mt-8 max-w-xl mx-auto relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-slate-400 group-focus-within:text-primary dark:group-focus-within:text-indigo-400 transition-colors">search</span>
                </div>
                <input type="text" placeholder="Cari masalah Anda (misal: Printer rusak...)" class="block w-full pl-11 pr-4 py-4 md:py-5 bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl md:rounded-3xl shadow-lg shadow-slate-200/50 dark:shadow-none focus:ring-primary dark:focus:ring-indigo-500/50 focus:border-primary dark:focus:border-indigo-500 sm:text-base font-medium transition-all group-hover:shadow-xl dark:text-white dark:placeholder:opacity-50">
            </div>
        </div>
    </div>

    <main class="max-w-4xl mx-auto px-6 md:px-10 py-16 space-y-12">
        <?php foreach ($faqs as $faqSection): ?>
        <section class="space-y-6">
            <div class="flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-4">
                <div class="p-3 bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-500/20 dark:to-slate-800 rounded-xl shadow-sm border border-indigo-100 dark:border-indigo-500/30 text-indigo-600 dark:text-indigo-400">
                    <span class="material-symbols-outlined text-xl"><?php echo $faqSection['icon']; ?></span>
                </div>
                <h2 class="text-2xl font-black font-headline text-slate-800 dark:text-white tracking-tight"><?php echo $faqSection['category']; ?></h2>
            </div>

            <div class="space-y-4">
                <?php foreach ($faqSection['items'] as $item): ?>
                <div x-data="{ expanded: false }" class="bg-white dark:bg-slate-900 border hover:border-primary/20 dark:border-slate-800 dark:hover:border-indigo-500/30 rounded-2xl shadow-sm transition-all overflow-hidden group">
                    <button @click="expanded = ! expanded" class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none">
                        <span class="text-[15px] font-bold text-slate-800 dark:text-slate-200 group-hover:text-primary dark:group-hover:text-indigo-400 transition-colors pr-6">
                            <?php echo htmlspecialchars($item['q']); ?>
                        </span>
                        <span class="material-symbols-outlined text-slate-400 transition-transform duration-300" :class="expanded ? 'rotate-180 text-primary dark:text-indigo-400' : ''">
                            expand_more
                        </span>
                    </button>
                    <!-- Expandable Content -->
                    <div x-show="expanded" x-collapse x-cloak class="px-6 pb-6 text-sm text-slate-600 dark:text-slate-400 font-medium leading-relaxed border-t border-slate-50 dark:border-slate-800 pt-4 bg-slate-50/50 dark:bg-slate-800/50">
                        <?php echo htmlspecialchars($item['a']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endforeach; ?>

        <!-- Support CTA -->
        <div class="mt-16 bg-gradient-to-br from-slate-900 to-indigo-950 rounded-[2.5rem] p-8 md:p-12 shadow-2xl relative overflow-hidden flex flex-col items-center text-center">
            <div class="absolute top-0 right-0 p-8 opacity-10">
                <span class="material-symbols-outlined text-[150px]">support_agent</span>
            </div>
            <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center text-white mb-6 backdrop-blur-md border border-white/20">
                <span class="material-symbols-outlined text-3xl">headset_mic</span>
            </div>
            <h3 class="text-2xl md:text-3xl font-black font-headline text-white tracking-tight mb-3">Tidak menemukan jawaban?</h3>
            <p class="text-indigo-200 text-sm md:text-base max-w-md font-medium mb-8">Tim Support IT kami selalu siap membantu Anda menyelesaikan kendala teknis yang kompleks.</p>
            <div class="flex flex-col sm:flex-row gap-4 relative z-10 w-full sm:w-auto">
                <a href="form_maintenance.php" class="px-8 py-4 bg-white text-indigo-900 font-bold rounded-xl shadow-lg hover:shadow-white/20 hover:scale-105 active:scale-95 transition-all text-sm uppercase tracking-widest text-center flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">build</span>
                    <span class="mt-0.5">Buat Tiket</span>
                </a>
            </div>
        </div>

    </main>

    <?php include __DIR__ . '/../includes/bottom_nav_user.php'; ?>
    <style>[x-cloak] { display: none !important; }</style>
</body>
</html>
