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
                "a" => "Pastikan Anda telah menginstal aplikasi OpenVPN Connect. Unduh profil konfigurasi (.ovpn) dari portal intranet, masukkan kredensial akun AD Anda, dan klik tombol 'Connect'. Jika muncul error sertifikat, mintalah pembaruan ke tim IT."
            ],
            [
                "q" => "Internet lambat atau terputus secara berkala?",
                "a" => "Cobalah matikan terlebih dahulu koneksi Wi-Fi Anda selama 10 detik lalu sambungkan kembali. Hindari menggunakan koneksi untuk streaming video resolusi tinggi saat sedang beroperasi pada aplikasi internal berat."
            ],
            [
                "q" => "Bagaimana cara menyambungkan printer via jaringan lokal?",
                "a" => "Pastikan printer dan komputer Anda terhubung ke Wi-Fi kantor yang sama. Buka Settings > Devices > Printers & Scanners di laptop Anda, klik 'Add Printer', dan pilih printer departemen Anda dari daftar yang terdeteksi."
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
                "q" => "Laptop mengalami Blue Screen atau Restart Sendiri secara tiba-tiba",
                "a" => "Segera simpan pekerjaan Anda jika sempat. Hentikan pemakaian paksa dan catat 'Stop Code' yang tertera pada layar biru. Segera laporkan melalui fitur form Maintenance agar tim IT dapat melakukan diagnosa mendalam."
            ],
            [
                "q" => "Mengatasi keyboard laptop yang sebagian tombolnya tidak merespon",
                "a" => "Lakukan restart perangkat Anda terlebih dahulu. Jika masalah berlanjut, pastikan tidak ada debu atau partikel kecil di bawah tombol. Gunakan keyboard eksternal USB sebagai alternatif darurat sementara Anda mengajukan permohonan servis ke IT Helpdesk."
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
            ],
            [
                "q" => "Bagaimana cara mengganti PIN Authenticator di Aplikasi?",
                "a" => "Masuk ke menu Profil Pengguna, lalu pilih sub-menu 'Keamanan Akun'. Klik 'Ubah PIN Authenticator', masukkan PIN lama Anda sekali, lalu masukkan PIN 6-digit baru sebanyak dua kali untuk verifikasi."
            ]
        ]
    ],
    [
        "category" => "Aplikasi & Sistem Operasi",
        "icon" => "terminal",
        "items" => [
            [
                "q" => "Bagaimana cara melakukan update software Microsoft Office berlisensi?",
                "a" => "Buka salah satu aplikasi Office (misal Word), pergi ke File > Account. Di bagian Product Information, klik 'Update Options' lalu pilih 'Update Now'. Pastikan laptop Anda terhubung ke internet yang stabil."
            ],
            [
                "q" => "Aplikasi browser Chrome sering crash atau hang?",
                "a" => "Cobalah membersihkan cache dan cookies browser Anda. Masuk ke Settings > Privacy and Security > Clear Browsing Data. Pilih kurun waktu 'All Time', centang 'Cached images and files', lalu klik 'Clear Data'."
            ]
        ]
    ],
    [
        "category" => "Panduan Pengadaan & Inventaris",
        "icon" => "shopping_cart",
        "items" => [
            [
                "q" => "Bagaimana alur pengajuan pengadaan perangkat baru (Procurement)?",
                "a" => "Masuk ke dashboard SIDIK-TI, buka modul 'Pengadaan Baru'. Isi formulir detail barang, spesifikasi teknis, estimasi anggaran, dan unggah brosur penawaran. Pengajuan akan ditinjau oleh Kepala Bidang sebelum diteruskan ke admin."
            ],
            [
                "q" => "Apa yang dimaksud dengan Stress Factor pada Sensus Aset?",
                "a" => "Stress Factor adalah indikator intensitas penggunaan perangkat Anda. Nilai 1.0 berarti pemakaian normal, >1.0 (misal 1.5) untuk perangkat lapangan dengan mobilitas tinggi, dan <1.0 (misal 0.8) untuk unit standby."
            ]
        ]
    ]
];

// Inisialisasi AI Search jika ada query
$searchQuery = $_GET['q'] ?? '';
$aiAnswer = '';

if (!empty($searchQuery)) {
    $aiAnswer = getAISearchAnswer($searchQuery, $faqs);
}

// Fungsi Parser Markdown Sederhana
function parseSimpleMarkdown($text) {
    $html = htmlspecialchars($text);
    $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*([\w\s\-]+)\*/', '<em>$1</em>', $html);
    $html = nl2br($html);
    return $html;
}

// Fungsi Komunikasi ke OpenRouter AI
function getAISearchAnswer($query, $faqsContext) {
    $apiKey = getenv('OPENROUTER_API_KEY') ?: '';
    $url = 'https://openrouter.ai/api/v1/chat/completions';
    
    // Format basis pengetahuan sebagai string referensi
    $contextStr = "";
    foreach ($faqsContext as $section) {
        $contextStr .= "Kategori: " . $section['category'] . "\n";
        foreach ($section['items'] as $item) {
            $contextStr .= "Pertanyaan: " . $item['q'] . "\nJawaban: " . $item['a'] . "\n\n";
        }
    }
    
    $systemPrompt = "Anda adalah Asisten IT Pintar SIDIK-TI (Sistem Informasi Diagnostik & Inventarisasi Komputer - Teknologi Informasi).
Tugas Anda adalah mendiagnosis dan memberikan solusi (troubleshooting) atas keluhan teknologi dari staff/karyawan kantor secara santun, terperinci, dan profesional dalam Bahasa Indonesia.

Berikut basis pengetahuan internal yang dapat Anda gunakan sebagai acuan utama:
$contextStr

Bila pertanyaan pengguna berkaitan dengan basis pengetahuan di atas, gunakan jawaban tersebut. Bila pertanyaan berupa masalah IT umum lainnya yang tidak ada di atas, berikan panduan troubleshooting langkah-demi-langkah yang logis, aman, dan mudah dimengerti.
PENTING: JANGAN menjawab pertanyaan yang tidak berkaitan sama sekali dengan IT/teknologi/perangkat kantor/Sistem SIDIK-TI. Jika pengguna bertanya di luar topik tersebut, tolak secara halus dan jelaskan bahwa Anda didesain khusus sebagai Asisten IT.
Gunakan cetak tebal (bold dengan **) untuk istilah penting agar mudah dibaca.";

    $data = [
        'model' => 'google/gemini-2.5-flash',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $query]
        ],
        'temperature' => 0.6,
        'max_tokens' => 1000
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: http://localhost/SIDIK-TI',
        'X-Title: SIDIK-TI AI Search'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        return "Maaf, gagal menghubungi server AI: " . curl_error($ch);
    }
    curl_close($ch);
    
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }
    
    if (isset($result['error'])) {
        return "Server AI mengembalikan error: " . ($result['error']['message'] ?? 'Koneksi Ditolak.');
    }
    return "Maaf, AI asisten sedang sibuk saat ini. Silakan coba kembali beberapa saat lagi.";
}
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
                Pusat Bantuan & Asisten AI
            </div>
            <h1 class="text-4xl md:text-6xl font-black font-headline tracking-tight text-slate-900 dark:text-white italic">
                Knowledge <span class="text-primary dark:text-indigo-400 italic">Base</span>
            </h1>
            <p class="mt-4 text-base md:text-lg leading-8 text-slate-500 dark:text-slate-400 max-w-2xl mx-auto font-medium">
                Cari jawaban cepat dari direktori internal kami atau ajukan pertanyaan langsung ke Kecerdasan Buatan (AI) terintegrasi kami!
            </p>

            <!-- Search Bar Form -->
            <form action="" method="GET" class="mt-8 max-w-xl mx-auto relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="material-symbols-outlined text-slate-400 group-focus-within:text-primary dark:group-focus-within:text-indigo-400 transition-colors">search</span>
                </div>
                <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" required placeholder="Tanyakan keluhan IT ke AI (misal: keyboard laptop rusak...)" class="block w-full pl-11 pr-28 py-4 md:py-5 bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-2xl md:rounded-3xl shadow-lg shadow-slate-200/50 dark:shadow-none focus:ring-primary dark:focus:ring-indigo-500/50 focus:border-primary dark:focus:border-indigo-500 sm:text-base font-medium transition-all group-hover:shadow-xl dark:text-white dark:placeholder:opacity-50">
                <button type="submit" class="absolute right-2 top-2 bottom-2 bg-indigo-600 text-white rounded-xl md:rounded-2xl px-4 text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition flex items-center justify-center gap-1.5 shadow-md shadow-indigo-200 dark:shadow-none">
                    Tanya AI <span class="material-symbols-outlined text-sm">smart_toy</span>
                </button>
            </form>
        </div>
    </div>

    <!-- AI Search Result Card (Only appears if a search is triggered) -->
    <?php if (!empty($searchQuery) && !empty($aiAnswer)): ?>
    <section class="max-w-4xl mx-auto px-6 md:px-10 mt-12">
        <div class="bg-gradient-to-br from-indigo-50/70 to-purple-50/70 dark:from-slate-900/50 dark:to-indigo-950/50 rounded-[2.5rem] p-6 md:p-8 border border-indigo-100/50 dark:border-indigo-500/20 shadow-xl shadow-indigo-500/5 relative overflow-hidden backdrop-blur-md">
            <!-- Decorative light glow -->
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-primary/10 rounded-full blur-2xl"></div>
            
            <div class="flex items-center justify-between border-b border-indigo-100/50 dark:border-slate-800/80 pb-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-600 text-white rounded-xl flex items-center justify-center shrink-0 shadow-md shadow-indigo-200 dark:shadow-none animate-pulse">
                        <span class="material-symbols-outlined text-xl">smart_toy</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-black text-slate-800 dark:text-white uppercase tracking-tight text-xs">Jawaban IT AI Pintar</h3>
                        <p class="text-[9px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 mt-0.5">SIDIK-TI AI Search Engine</p>
                    </div>
                </div>
                <span class="px-3 py-1 bg-white/80 dark:bg-slate-800/80 rounded-full text-[8px] font-mono border border-indigo-100 dark:border-slate-700 text-slate-500 dark:text-slate-400">
                    MODEL: GEMINI-2.5-FLASH
                </span>
            </div>
            
            <!-- Render AI Answer with basic markdown parser -->
            <div class="prose prose-indigo dark:prose-invert max-w-none text-[15px] leading-relaxed text-slate-700 dark:text-slate-300 font-medium font-body">
                <?php echo parseSimpleMarkdown($aiAnswer); ?>
            </div>
            
            <div class="mt-6 pt-4 border-t border-indigo-100/30 dark:border-slate-800/50 flex flex-col sm:flex-row justify-between items-center gap-3 text-[10px] text-slate-400">
                <span class="font-semibold italic">Pertanyaan Anda: "<?php echo htmlspecialchars($searchQuery); ?>"</span>
                <div class="flex items-center gap-2">
                    <span class="mr-1">Apakah ini membantu?</span>
                    <button onclick="alert('Terima kasih atas feedback Anda!')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition"><span class="material-symbols-outlined text-sm text-emerald-500">thumb_up</span></button>
                    <button onclick="alert('Terima kasih atas feedback Anda!')" class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition"><span class="material-symbols-outlined text-sm text-rose-500">thumb_down</span></button>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Main FAQ Directories -->
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

        <!-- Support IT Helpdesk CTA -->
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
