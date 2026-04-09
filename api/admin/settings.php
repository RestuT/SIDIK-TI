<?php
ob_start();
session_start();

require_once __DIR__ . '/../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

// --- LOGIKA SIMPAN PROFIL ---
$msg = "";
$user_id = $_SESSION['user_id'];

if (isset($_POST['save_profile'])) {
    $full_name        = $_POST['full_name'] ?? '';
    $username         = $_POST['username'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        $userRef    = $db->collection('users')->document($user_id);
        $updateData = [
            ['path' => 'full_name', 'value' => $full_name],
            ['path' => 'username',  'value' => $username]
        ];
        $userRef->update($updateData);
        $_SESSION['user'] = $username;
        
        if (!empty($new_password)) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $userRef->update([['path' => 'password', 'value' => $hashed_password]]);
                $msg = "success_pw";
            } else {
                $msg = "err_pw_mismatch";
            }
        } else {
            $msg = "success";
        }
    } catch (Exception $e) {
        $msg = "error_db";
    }
}

// --- AMBIL DATA PROFIL TERBARU ---
$userSnap      = $db->collection('users')->document($user_id)->snapshot();
$current_admin = $userSnap->exists() ? $userSnap->data() : [];
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | System Settings</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3525cd",
                        "primary-container": "#4f46e5",
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
            vertical-align: middle;
        }
        .fill-1 { font-variation-settings: 'FILL' 1; }
        
        @keyframes slide-up {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up { animation: slide-up 0.4s ease both; }

        /* Realtime badge pulse */
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
        .live-dot { animation: blink 1.5s ease-in-out infinite; }
    </style>
</head>
<body class="bg-surface-container-low dark:bg-slate-950 font-body text-on-surface dark:text-slate-200 antialiased overflow-x-hidden min-h-screen transition-colors duration-500">
    
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <!-- Header Bar -->
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl z-20">
            <div>
                <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface dark:text-white tracking-tight italic uppercase leading-none">System <span class="text-primary italic md:text-3xl">Settings</span></h1>
                <p class="text-[10px] text-on-surface-variant dark:text-slate-400 font-black uppercase tracking-[0.2em] mt-1">Configuration &amp; Preferences</p>
            </div>
            <!-- Live Indicator -->
            <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-100">
                <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest" id="sync-status">Settings Synced</span>
            </div>
        </header>

        <div class="p-4 md:p-8 max-w-5xl mx-auto w-full space-y-8 md:space-y-10">

            <!-- Alerts -->
            <?php if($msg === 'success' || $msg === 'success_pw'): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-8 py-5 rounded-3xl flex items-center gap-4 animate-slide-up shadow-sm">
                    <span class="material-symbols-outlined text-2xl fill-1">check_circle</span>
                    <div class="flex flex-col">
                        <p class="font-headline font-bold text-sm tracking-tight leading-none uppercase">Berhasil Disimpan!</p>
                        <p class="text-[10px] font-medium opacity-70 mt-1"><?php 
                            if($msg === 'success_pw') echo 'Sesi Anda telah diperbarui dengan kata sandi baru.'; 
                            else echo 'Informasi profil Anda telah diperbarui secara real-time.';
                        ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if($msg === 'err_pw_mismatch'): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-700 px-8 py-5 rounded-3xl flex items-center gap-4 animate-slide-up shadow-sm">
                    <span class="material-symbols-outlined text-2xl fill-1">error</span>
                    <div class="flex flex-col">
                        <p class="font-headline font-bold text-sm tracking-tight leading-none uppercase">Gagal Menyimpan!</p>
                        <p class="text-[10px] font-medium opacity-70 mt-1">Konfirmasi kata sandi baru Anda tidak sesuai.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ===== APPEARANCE ===== -->
            <section class="space-y-6">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-2xl">palette</span>
                    <h2 class="font-headline text-xl font-bold">Personalisasi Tema</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <button onclick="setTheme('light')" id="btn-light" class="group relative bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] border-2 transition-all duration-300 text-left overflow-hidden">
                        <div class="flex justify-between items-start mb-10">
                            <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-slate-800 flex items-center justify-center text-indigo-600">
                                <span class="material-symbols-outlined">light_mode</span>
                            </div>
                            <div id="check-light" class="hidden">
                                <span class="material-symbols-outlined text-emerald-500 fill-1">check_circle</span>
                            </div>
                        </div>
                        <h3 class="text-xl font-black italic uppercase tracking-tighter mb-1">Light Mode</h3>
                        <p class="text-xs text-on-surface-variant dark:text-slate-400">Tampilan bersih dan terang, optimal untuk penggunaan di siang hari.</p>
                        <div class="mt-6 flex gap-2 opacity-40">
                            <div class="h-2 w-12 bg-slate-200 rounded-full"></div>
                            <div class="h-2 w-4 bg-primary rounded-full"></div>
                        </div>
                    </button>

                    <button onclick="setTheme('dark')" id="btn-dark" class="group relative bg-white dark:bg-slate-900 p-6 rounded-[2.5rem] border-2 transition-all duration-300 text-left overflow-hidden">
                        <div class="flex justify-between items-start mb-10">
                            <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center text-indigo-400">
                                <span class="material-symbols-outlined">dark_mode</span>
                            </div>
                            <div id="check-dark" class="hidden">
                                <span class="material-symbols-outlined text-indigo-400 fill-1">check_circle</span>
                            </div>
                        </div>
                        <h3 class="text-xl font-black italic uppercase tracking-tighter mb-1">Dark Mode</h3>
                        <p class="text-xs text-on-surface-variant dark:text-slate-400">Tampilan gelap yang elegan, mengurangi kelelahan mata di malam hari.</p>
                        <div class="mt-6 flex gap-2 opacity-40">
                            <div class="h-2 w-12 bg-slate-700 rounded-full"></div>
                            <div class="h-2 w-4 bg-indigo-500 rounded-full"></div>
                        </div>
                    </button>
                </div>
            </section>

            <!-- ===== PROFIL ADMIN ===== -->
            <section class="space-y-6">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-2xl">account_circle</span>
                    <h2 class="font-headline text-xl font-bold">Manajemen Profil Admin</h2>
                </div>

                <form action="" method="POST" class="bg-white dark:bg-slate-900 rounded-[3rem] p-10 border border-outline-variant/10 shadow-sm space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant dark:text-slate-400 ml-1">Nama Lengkap</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($current_admin['full_name'] ?? ''); ?>" required class="w-full bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl p-4 font-bold text-sm outline-none focus:ring-2 focus:ring-primary/20 transition">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant dark:text-slate-400 ml-1">Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($current_admin['username'] ?? ''); ?>" required class="w-full bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl p-4 font-bold text-sm outline-none focus:ring-2 focus:ring-primary/20 transition">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant dark:text-slate-400 ml-1">Ganti Password (Opsional)</label>
                            <input type="password" name="new_password" placeholder="••••••••" class="w-full bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl p-4 font-bold text-sm outline-none focus:ring-2 focus:ring-primary/20 transition">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant dark:text-slate-400 ml-1">Konfirmasi Password</label>
                            <input type="password" name="confirm_password" placeholder="••••••••" class="w-full bg-surface-container-low dark:bg-slate-800 border-0 rounded-2xl p-4 font-bold text-sm outline-none focus:ring-2 focus:ring-primary/20 transition">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-outline-variant/10 flex justify-end">
                        <button type="submit" name="save_profile" class="px-8 py-4 bg-primary text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-primary/20 hover:scale-105 transition active:scale-95">
                            Update Profil Saya
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <script>
    // =====================================================
    // TEMA
    // =====================================================
    function updateUI(theme) {
        const isDark = theme === 'dark';
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        const btnLight  = document.getElementById('btn-light');
        const btnDark   = document.getElementById('btn-dark');
        const chkLight  = document.getElementById('check-light');
        const chkDark   = document.getElementById('check-dark');
        if (isDark) {
            btnDark.classList.add('border-primary', 'shadow-2xl', 'shadow-primary/10');
            btnDark.classList.remove('border-transparent');
            btnLight.classList.add('border-transparent');
            btnLight.classList.remove('border-primary', 'shadow-2xl');
            chkDark.classList.remove('hidden');
            chkLight.classList.add('hidden');
        } else {
            btnLight.classList.add('border-primary', 'shadow-2xl', 'shadow-primary/10');
            btnLight.classList.remove('border-transparent');
            btnDark.classList.add('border-transparent');
            btnDark.classList.remove('border-primary', 'shadow-2xl');
            chkLight.classList.remove('hidden');
            chkDark.classList.add('hidden');
        }
    }

    function setTheme(theme) {
        localStorage.theme = theme;
        updateUI(theme);
    }

    document.addEventListener('DOMContentLoaded', () => {
        updateUI(localStorage.theme || 'light');
    });
    </script>
</body>
</html>
