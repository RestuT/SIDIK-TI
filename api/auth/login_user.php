<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

require_csrf_token();

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Query mencari user berdasarkan username
    $userData = null;

    if ($db) { // Use Firestore (Verel/Cloud)
        $usersRef = $db->collection('users');
        $query = $usersRef->where('username', '=', $username);
        $documents = $query->documents();

        foreach ($documents as $document) {
            if ($document->exists()) {
                $userData = $document->data();
                $userData['id'] = $document->id();
            }
        }
    } else if ($conn) { // Use MySQL (Local)
        $username = mysqli_real_escape_string($conn, $username);
        $sql = "SELECT * FROM users WHERE username = '$username'";
        $result = mysqli_query($conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            $userData = mysqli_fetch_assoc($result);
        }
    } else {
        $error = "Terjadi kesalahan: Tidak dapat terhubung ke database Firestore maupun MySQL.";
    }

    if ($userData) {
        // 2. Verifikasi Password (menggunakan password_verify jika dipassword_hash)
        if (password_verify($password, $userData['password'])) {
            
            // 3. Simpan data ke Session agar Foreign Key di submissions valid
            $_SESSION['user'] = $userData['username'];
            $_SESSION['user_id'] = $userData['id']; 
            $_SESSION['role'] = $userData['role'];

            header("Location: ../modules_user/dashboard_user.php");
            exit();
        } else {
            $error = "Kata sandi salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Login User</title>
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
    </style>
    <?php include_once __DIR__ . '/../includes/firebase_js.php'; ?>
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden">
    <main class="min-h-screen flex flex-col md:flex-row items-stretch">
        <!-- Kolom Kiri: Form Login -->
        <section class="flex-1 flex items-center justify-center p-6 md:p-12 lg:p-20 bg-surface order-2 md:order-1">
            <div class="w-full max-w-md space-y-10">
                <!-- Identitas Brand -->
                <div class="flex flex-col items-center md:items-start space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary to-primary-container flex items-center justify-center shadow-lg shadow-primary/20">
                            <span class="material-symbols-outlined text-white text-3xl">hub</span>
                        </div>
                        <h1 class="font-headline font-extrabold text-2xl tracking-tight text-indigo-900">
                            SIDIK-TI
                        </h1>
                    </div>
                    <div class="text-center md:text-left">
                        <h2 class="font-headline text-3xl font-bold text-on-surface">Selamat Datang Kembali</h2>
                        <p class="text-on-surface-variant mt-2">Masuk untuk mengelola tiket dan infrastruktur TI Anda.</p>
                    </div>
                </div>

                <!-- Form Login -->
                <div class="bg-surface-container-lowest p-8 md:p-10 rounded-lg shadow-sm shadow-indigo-500/5 border border-outline-variant/10">
                    <?php if(isset($error)): ?>
                        <div class="bg-error-container text-on-error-container p-4 rounded-2xl mb-6 flex items-center gap-3 animate-pulse">
                            <span class="material-symbols-outlined">error</span>
                            <p class="text-sm font-semibold"><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="" class="space-y-6" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <!-- Input Username -->
                        <div class="space-y-2">
                            <label class="block font-headline text-sm font-semibold text-on-surface-variant ml-1" for="username">Username</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-xl">person</span>
                                </div>
                                <input class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-on-surface placeholder:text-outline/60" 
                                    id="username" name="username" placeholder="Masukkan username" type="text" required/>
                            </div>
                        </div>

                        <!-- Input Password -->
                        <div class="space-y-2">
                            <div class="flex justify-between items-center ml-1">
                                <label class="block font-headline text-sm font-semibold text-on-surface-variant" for="password">Password</label>
                                <a class="text-xs font-semibold text-primary hover:text-primary-container transition-colors" href="#">Lupa Password?</a>
                            </div>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-xl">lock</span>
                                </div>
                                <input class="block w-full pl-12 pr-4 py-4 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-on-surface placeholder:text-outline/60" 
                                    id="password" name="password" placeholder="••••••••" type="password" required/>
                            </div>
                        </div>

                        <!-- Login Button -->
                        <button class="w-full py-4 px-6 bg-gradient-to-br from-primary to-primary-container text-white font-headline font-bold rounded-2xl shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:scale-[1.02] active:scale-95 transition-all duration-300 flex items-center justify-center gap-2" 
                            type="submit" name="login">
                            <span>Masuk ke Sistem</span>
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                    </form>
                </div>

                <!-- Footer Links -->
                <div class="pt-6 text-center text-sm text-on-surface-variant">
                    <p>Belum memiliki akses? <a class="text-primary font-bold hover:underline" href="register_user.php">Daftar Sekarang</a></p>
                    <p class="mt-2"><a href="../index.php" class="text-outline font-medium hover:text-primary">Kembali ke Beranda</a></p>
                </div>
            </div>
        </section>

        <!-- Kolom Kanan: Visual Area -->
        <section class="hidden md:flex flex-1 relative overflow-hidden bg-gradient-to-br from-indigo-600 via-primary to-purple-600 order-1 md:order-2 items-center justify-center p-12">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl -mr-20 -mt-20"></div>
            <div class="absolute bottom-0 left-0 w-80 h-80 bg-purple-500/20 rounded-full blur-3xl -ml-20 -mb-20"></div>
            
            <div class="relative z-10 max-w-lg text-center text-white">
                <div class="mb-8 inline-flex items-center justify-center p-4 bg-white/10 backdrop-blur-md rounded-3xl">
                    <span class="material-symbols-outlined text-5xl">verified_user</span>
                </div>
                <blockquote class="space-y-6">
                    <p class="font-headline text-3xl lg:text-4xl font-extrabold leading-tight tracking-tight">
                        "Teknologi adalah alat, namun efisiensi adalah cara kita melayani sesama."
                    </p>
                    <footer class="flex flex-col items-center">
                        <div class="w-12 h-1 bg-white/30 rounded-full mb-4"></div>
                        <cite class="not-italic font-bold text-indigo-100 text-lg">SIDIK-TI Workspace</cite>
                        <span class="text-white/60 text-sm uppercase tracking-widest mt-1">Sistem Informasi Dukungan TI</span>
                    </footer>
                </blockquote>
            </div>
        </section>
    </main>
</body>
</html>
