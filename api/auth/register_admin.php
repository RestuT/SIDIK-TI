<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

$message = "";
$error = "";

if (isset($_POST['register_admin'])) {
    require_csrf_token();
    
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $two_fa   = $_POST['two_fa_code'];

    // Validasi
    if ($password !== $confirm) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($two_fa) !== 6) {
        $error = "Kode 2FA harus tepat 6 digit angka!";
    } else {
        try {
            // Cek username unik
            $userExists = false;
            if ($db) {
                $userRef = $db->collection('users')->where('username', '=', $username)->limit(1)->documents();
                $userExists = !$userRef->isEmpty();
            } else if ($conn) {
                $username_esc = mysqli_real_escape_string($conn, $username);
                $res = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_esc' LIMIT 1");
                $userExists = (mysqli_num_rows($res) > 0);
            }
            
            if ($userExists) {
                $error = "Username admin sudah digunakan!";
            } else {
                // Enkripsi password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert data
                $saved = false;
                if ($db) { // Firestore
                    $db->collection('users')->add([
                        'username' => $username,
                        'password' => $hashed_password,
                        'full_name' => $fullname,
                        'role' => 'admin',
                        'two_fa_code' => $two_fa,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $saved = true;
                } else if ($conn) { // MySQL
                    $username = mysqli_real_escape_string($conn, $username);
                    $fullname = mysqli_real_escape_string($conn, $fullname);
                    $two_fa = mysqli_real_escape_string($conn, $two_fa);
                    
                    $sql = "INSERT INTO users (username, password, full_name, role, two_fa_code, created_at) 
                            VALUES ('$username', '$hashed_password', '$fullname', 'admin', '$two_fa', NOW())";
                    if (mysqli_query($conn, $sql)) {
                        $saved = true;
                    }
                }
                
                if ($saved) {
                    header("Location: login_admin.php?pesan=reg_sukses");
                    exit();
                } else {
                    $error = "Gagal menyimpan data: Tidak ada koneksi ke database Firestore maupun MySQL.";
                }
            }
        } catch (Exception $e) {
            $error = "Gagal mendaftar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Register Administrator</title>
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
                        "primary": "#1e293b",
                        "primary-container": "#334155",
                        "secondary": "#4f46e5",
                        "active-glow": "#6366f1",
                        "on-surface": "#0f172a",
                        "on-surface-variant": "#64748b",
                        "surface": "#f8fafc",
                        "surface-container-lowest": "#ffffff",
                        "outline-variant": "#e2e8f0",
                        "error-container": "#fee2e2",
                        "on-error-container": "#991b1b",
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
    <?php include_once __DIR__ . '/../includes/firebase_js.php'; ?>

    <script>
        // Inject base tag to preserve relative link resolution
        if (!document.querySelector('base')) {
            var base = document.createElement('base');
            base.href = window.location.href.split('?')[0];
            document.head.appendChild(base);
        }
        // Mask URL to Pretty Path
        if (window.history.replaceState) {
            var path = window.location.pathname;
            var search = window.location.search;
            if (path.includes('/api/')) {
                window.history.replaceState(null, null, path.replace('/api/', '/') + search);
            }
        }
    </script>
</head>
<body class="bg-primary font-body text-on-surface antialiased overflow-x-hidden">
    <main class="min-h-screen flex flex-col md:flex-row items-stretch">
        <!-- Kolom Kiri: Form Registrasi -->
        <section class="flex-1 flex items-center justify-center p-6 md:p-12 lg:p-20 bg-surface order-1">
            <div class="w-full max-w-xl space-y-10 py-12 md:py-0">
                <!-- Identitas Brand -->
                <div class="flex flex-col items-center md:items-start space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="../assets/img/logo.png" alt="Logo" class="h-12 md:h-14 w-auto">
                        <h1 class="font-headline font-extrabold text-2xl tracking-tight text-primary">
                            SIDIK-TI <span class="text-secondary tracking-widest text-[10px] uppercase font-black ml-2 px-2 py-0.5 bg-secondary/10 rounded-full">Admin Console</span>
                        </h1>
                    </div>
                    <div class="text-center md:text-left">
                        <h2 class="font-headline text-3xl font-bold text-on-surface tracking-tight">Daftar Akun Administrator</h2>
                        <p class="text-on-surface-variant mt-2 font-medium">Otoritas tinggi memerlukan tanggung jawab enkripsi yang tepat.</p>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="bg-surface-container-lowest p-8 md:p-10 rounded-3xl shadow-2xl shadow-primary/10 border border-outline-variant/10">
                    <?php if($error): ?>
                        <div class="bg-error-container text-on-error-container p-4 rounded-2xl mb-6 flex items-center gap-3 border border-red-200">
                            <span class="material-symbols-outlined">gpp_maybe</span>
                            <p class="text-xs font-bold uppercase tracking-tight"><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Username -->
                            <div class="space-y-2">
                                <label class="block font-headline text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] ml-1">Admin Username</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-on-surface-variant/40 group-focus-within:text-secondary transition-colors">
                                        <span class="material-symbols-outlined text-lg">terminal</span>
                                    </div>
                                    <input class="block w-full pl-10 pr-4 py-4 bg-surface border border-outline-variant/50 rounded-2xl focus:ring-4 focus:ring-secondary/10 focus:border-secondary/50 focus:bg-white transition-all outline-none text-sm font-medium" 
                                        name="username" placeholder="sys-admin-01" type="text" required/>
                                </div>
                            </div>
                            <!-- Fullname -->
                            <div class="space-y-2">
                                <label class="block font-headline text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] ml-1">Full Legal Name</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-on-surface-variant/40 group-focus-within:text-secondary transition-colors">
                                        <span class="material-symbols-outlined text-lg">badge</span>
                                    </div>
                                    <input class="block w-full pl-10 pr-4 py-4 bg-surface border border-outline-variant/50 rounded-2xl focus:ring-4 focus:ring-secondary/10 focus:border-secondary/50 focus:bg-white transition-all outline-none text-sm font-medium" 
                                        name="fullname" placeholder="Administrator Name" type="text" required/>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Password -->
                            <div class="space-y-2">
                                <label class="block font-headline text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] ml-1">Master Password</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-on-surface-variant/40 group-focus-within:text-secondary transition-colors">
                                        <span class="material-symbols-outlined text-lg">key</span>
                                    </div>
                                    <input class="block w-full pl-10 pr-4 py-4 bg-surface border border-outline-variant/50 rounded-2xl focus:ring-4 focus:ring-secondary/10 focus:border-secondary/50 focus:bg-white transition-all outline-none text-sm font-medium" 
                                        name="password" placeholder="••••••••" type="password" required/>
                                </div>
                            </div>
                            <!-- Confirm Password -->
                            <div class="space-y-2">
                                <label class="block font-headline text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] ml-1">Confirm Identity</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-on-surface-variant/40 group-focus-within:text-secondary transition-colors">
                                        <span class="material-symbols-outlined text-lg">shield</span>
                                    </div>
                                    <input class="block w-full pl-10 pr-4 py-4 bg-surface border border-outline-variant/50 rounded-2xl focus:ring-4 focus:ring-secondary/10 focus:border-secondary/50 focus:bg-white transition-all outline-none text-sm font-medium" 
                                        name="confirm_password" placeholder="••••••••" type="password" required/>
                                </div>
                            </div>
                        </div>

                        <!-- 2FA Section -->
                        <div class="p-6 bg-secondary/5 rounded-3xl border border-secondary/10 space-y-4">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-secondary">phonelink_lock</span>
                                <label class="block font-headline text-[10px] font-black text-secondary uppercase tracking-[0.2em]">Setup Kode 2FA (6 Digit PIN)</label>
                            </div>
                            <input type="text" name="two_fa_code" maxlength="6" required placeholder="0 0 0 0 0 0" 
                                class="w-full py-5 bg-white border border-secondary/20 rounded-2xl focus:ring-4 focus:ring-secondary/10 focus:border-secondary/50 outline-none font-headline font-black text-center text-3xl tracking-[0.5em] text-secondary placeholder:opacity-20 shadow-inner">
                            <p class="text-[10px] text-center text-on-surface-variant/70 italic leading-relaxed">Simpan kode ini dengan aman. Anda akan memerlukannya untuk setiap sesi otentikasi admin.</p>
                        </div>

                        <button type="submit" name="register_admin" class="w-full py-5 px-6 bg-primary text-white font-headline font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-primary/20 hover:bg-secondary hover:shadow-secondary/30 hover:scale-[1.02] active:scale-95 transition-all duration-500 flex items-center justify-center gap-3 group">
                            <span>Initialize Admin Profile</span>
                            <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">bolt</span>
                        </button>
                    </form>
                </div>

                <div class="pt-6 text-center text-sm text-on-surface-variant">
                    <p>Kembali ke <a class="text-secondary font-bold hover:underline" href="login_admin.php">Login Konsol</a></p>
                </div>
            </div>
        </section>

        <!-- Kolom Kanan: Visual Area -->
        <section class="hidden md:flex flex-1 relative overflow-hidden bg-primary items-center justify-center p-12">
            <div class="absolute inset-0">
                <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_center,_rgba(255,255,255,0.05)_1.5px,_transparent_1.5px)] bg-[size:40px_40px]"></div>
                <!-- Animated Gradients -->
                <div class="absolute top-1/4 right-[-10%] w-96 h-96 bg-secondary/20 blur-[100px] rounded-full"></div>
                <div class="absolute bottom-1/4 left-[-10%] w-96 h-96 bg-active-glow/10 blur-[100px] rounded-full"></div>
            </div>
            <div class="relative z-10 max-w-sm text-center text-white space-y-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-white/5 backdrop-blur-2xl rounded-[2.5rem] border border-white/10 shadow-2xl relative">
                    <span class="material-symbols-outlined text-5xl">hub</span>
                    <div class="absolute -top-1 -right-1 w-4 h-4 bg-secondary rounded-full animate-ping"></div>
                </div>
                <div class="space-y-4">
                    <h2 class="font-headline text-4xl font-extrabold leading-tight tracking-tight uppercase italic">
                        Enterprise <span class="text-secondary">Security</span> Gateway
                    </h2>
                    <div class="w-20 h-1 bg-gradient-to-r from-transparent via-secondary to-transparent mx-auto rounded-full opacity-50"></div>
                    <p class="text-white/60 font-medium leading-relaxed italic text-sm">
                        "Enforce strict policy, maintain zero-trust integrity, and orchestrate the digital landscape from a single unified console."
                    </p>
                </div>
                <!-- Status Matrix Mockup -->
                <div class="grid grid-cols-3 gap-2 opacity-30 mt-12">
                    <div class="h-1 bg-white/20 rounded-full overflow-hidden"><div class="h-full bg-secondary w-full"></div></div>
                    <div class="h-1 bg-white/20 rounded-full overflow-hidden"><div class="h-full bg-secondary w-[80%]"></div></div>
                    <div class="h-1 bg-white/20 rounded-full overflow-hidden"><div class="h-full bg-secondary w-[60%]"></div></div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>

