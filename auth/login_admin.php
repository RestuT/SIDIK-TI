<?php
session_start();
// PERBAIKAN: Pastikan file database.php benar-benar terhubung
include '../config/database.php'; 
include '../config/csrf_helper.php';

require_csrf_token();

$step = 1; 
$error = "";

// Tahap 1: Validasi Username & Password
if (isset($_POST['login_step1'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hanya mencari user dengan role admin menggunakan PREPARED STATEMENTS
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? AND role = 'admin'");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // VERIFIKASI: Menggunakan password_verify untuk mengecek hash
        if (password_verify($password, $row['password'])) {
            $_SESSION['temp_admin_id'] = $row['id'];
            $_SESSION['temp_admin_user'] = $row['username'];
            $step = 2; 
        } else {
            $error = "Kata sandi salah!";
        }
    } else {
        $error = "Akun admin tidak ditemukan!";
    }
}

// Tahap 2: Validasi Kode 2FA
if (isset($_POST['verify_2fa'])) {
    // Keamanan: Cek jika session sementara hilang
    if (!isset($_SESSION['temp_admin_id'])) {
        header("Location: login_admin.php");
        exit();
    }

    $code = $_POST['two_fa_code'];
    $admin_id = $_SESSION['temp_admin_id'];

    $stmt2 = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ? AND two_fa_code = ?");
    mysqli_stmt_bind_param($stmt2, "is", $admin_id, $code);
    mysqli_stmt_execute($stmt2);
    $result = mysqli_stmt_get_result($stmt2);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        
        // Login Berhasil - Set Session Utama
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user'] = $row['username'];
        $_SESSION['role'] = 'admin';
        
        // Bersihkan session sementara
        unset($_SESSION['temp_admin_id']);
        unset($_SESSION['temp_admin_user']);
        
        header("Location: ../admin/dashboard_admin.php");
        exit();
    } else {
        $step = 2; // Tetap di tahap 2 jika kode salah
        $error = "Kode 2FA tidak valid!";
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Login Admin</title>
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
    </style>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden">
    <main class="min-h-screen flex flex-col md:flex-row items-stretch">
        <!-- Kolom Kiri: Form Login -->
        <section class="flex-1 flex items-center justify-center p-6 md:p-12 lg:p-20 bg-surface order-2 md:order-1">
            <div class="w-full max-w-md space-y-10">
                <!-- Identitas Brand -->
                <div class="flex flex-col items-center md:items-start space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-900 to-primary flex items-center justify-center shadow-lg shadow-indigo-900/20">
                            <span class="material-symbols-outlined text-white text-3xl">terminal</span>
                        </div>
                        <h1 class="font-headline font-extrabold text-2xl tracking-tight text-indigo-900">
                            SIDIK-TI ADMIN
                        </h1>
                    </div>
                    <div class="text-center md:text-left">
                        <h2 class="font-headline text-3xl font-bold text-on-surface">Secure Access</h2>
                        <p class="text-on-surface-variant mt-2 uppercase tracking-widest text-xs font-bold">IT Helpdesk - Managed Panel</p>
                    </div>
                </div>

                <!-- Form Login Area -->
                <div class="bg-surface-container-lowest p-8 md:p-10 rounded-lg shadow-sm shadow-indigo-500/5 border border-outline-variant/10">
                    <?php if($error): ?>
                        <div class="bg-error-container text-on-error-container p-4 rounded-2xl mb-6 flex items-center gap-3">
                            <span class="material-symbols-outlined">warning</span>
                            <p class="text-sm font-semibold uppercase tracking-tight"><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if($step == 1): ?>
                        <!-- TAHAP 1: Username & Password -->
                        <form action="" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="space-y-2">
                                <label class="block font-headline text-sm font-semibold text-on-surface-variant ml-1">ID Administrator</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                                    </div>
                                    <input class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-on-surface placeholder:text-outline/60" 
                                        name="username" placeholder="Username" type="text" required autocomplete="off"/>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block font-headline text-sm font-semibold text-on-surface-variant ml-1">Password</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-xl">lock</span>
                                    </div>
                                    <input class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-on-surface placeholder:text-outline/60" 
                                        name="password" placeholder="••••••••" type="password" required/>
                                </div>
                            </div>

                            <button type="submit" name="login_step1" 
                                class="w-full py-4 px-6 bg-indigo-900 text-white font-headline font-bold rounded-2xl shadow-lg shadow-indigo-900/30 hover:bg-primary hover:shadow-primary/50 hover:scale-[1.02] active:scale-95 transition-all duration-300 flex items-center justify-center gap-2">
                                <span>Lanjut Ke Verifikasi</span>
                                <span class="material-symbols-outlined text-xl">arrow_forward</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- TAHAP 2: 2FA Verification -->
                        <form action="" method="POST" class="space-y-10">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="text-center">
                                <p class="text-xs text-on-surface-variant uppercase font-bold tracking-widest mb-1">Identitas Terverifikasi</p>
                                <p class="text-lg text-indigo-950 font-black">Halo, <?php echo $_SESSION['temp_admin_user']; ?>!</p>
                            </div>

                            <div class="space-y-4 text-center">
                                <label class="block text-xs font-black text-on-surface-variant uppercase tracking-widest">Input 6-Digit PIN 2FA</label>
                                <input type="text" name="two_fa_code" maxlength="6" required autofocus 
                                    class="w-full text-center text-4xl tracking-[1rem] py-4 bg-primary-fixed/20 border-2 border-primary-fixed rounded-2xl focus:border-primary focus:ring-0 transition-all outline-none font-black text-primary" 
                                    placeholder="000000"/>
                            </div>

                            <div class="space-y-4">
                                <button type="submit" name="verify_2fa" 
                                    class="w-full bg-primary text-white font-headline font-bold py-4 rounded-2xl shadow-lg hover:shadow-primary/50 transition-all active:scale-95 uppercase tracking-widest text-xs">
                                    Buka Dashboard
                                </button>
                                <a href="login_admin.php" class="block text-center text-xs text-on-surface-variant font-bold uppercase hover:text-primary transition-colors">Kembali ke Tahap 1</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Footer Links -->
                <div class="pt-6 text-center text-sm text-on-surface-variant space-y-2">
                    <p>Butuh akses administratif? <a class="text-indigo-900 font-bold hover:underline" href="register_admin.php">Hubungi Lead IT</a></p>
                    <p><a href="../index.php" class="text-outline font-medium hover:text-indigo-900">Halaman Utama</a></p>
                </div>
            </div>
        </section>

        <!-- Kolom Kanan: Visual Area (Admin Variation) -->
        <section class="hidden md:flex flex-1 relative overflow-hidden bg-gradient-to-br from-slate-900 via-indigo-950 to-indigo-900 order-1 md:order-2 items-center justify-center p-12">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl -mr-20 -mt-20"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl -ml-20 -mb-20"></div>
            
            <div class="relative z-10 max-w-lg text-center text-white">
                <div class="mb-8 inline-flex items-center justify-center p-6 bg-white/5 backdrop-blur-xl rounded-[2.5rem] border border-white/10 shadow-2xl">
                    <span class="material-symbols-outlined text-6xl text-primary-fixed-dim" style="font-variation-settings: 'FILL' 1;">security</span>
                </div>
                <div class="space-y-6">
                    <h2 class="font-headline text-4xl lg:text-5xl font-extrabold leading-tight tracking-tight uppercase italic">
                        The Core of <span class="text-primary-fixed-dim">Infrastructure</span>
                    </h2>
                    <div class="w-16 h-1 bg-primary-fixed-dim rounded-full mx-auto"></div>
                    <p class="text-indigo-100/60 font-medium tracking-wide">
                        Sistem Validasi Bertingkat (Layered Authentication) untuk integritas data dan keamanan operasional.
                    </p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>