<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

if (isset($_POST['register'])) {
    require_csrf_token();
    
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $dept     = $_POST['department'];
    $jabatan  = $_POST['jabatan'];
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // 1. Validasi Password
    if ($password !== $confirm) {
        $error = "Konfirmasi password tidak sesuai!";
    } else {
        try {
            // 2. Cek apakah username sudah ada
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
                $error = "Username sudah terdaftar!";
            } else {
                // 3. Enkripsi Password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 4. Tentukan Role berdasarkan Jabatan
                $final_role = 'user';
                $head_positions = ['Kepala Seksi', 'Kepala Bidang', 'Sekretaris'];
                if (in_array($jabatan, $head_positions)) {
                    $final_role = 'head';
                }

                // 5. Insert data
                $saved = false;
                if ($db) { // Firestore
                    $db->collection('users')->add([
                        'username' => $username,
                        'password' => $hashed_password,
                        'full_name' => $fullname,
                        'department' => $dept,
                        'jabatan' => $jabatan,
                        'role' => $final_role, 
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    $saved = true;
                } else if ($conn) { // MySQL
                    $username = mysqli_real_escape_string($conn, $username);
                    $fullname = mysqli_real_escape_string($conn, $fullname);
                    $dept = mysqli_real_escape_string($conn, $dept);
                    $jabatan = mysqli_real_escape_string($conn, $jabatan);
                    
                    $sql = "INSERT INTO users (username, password, full_name, department, jabatan, role, created_at) 
                            VALUES ('$username', '$hashed_password', '$fullname', '$dept', '$jabatan', '$final_role', NOW())";
                    if (mysqli_query($conn, $sql)) {
                        $saved = true;
                    }
                }

                if ($saved) {
                    header("Location: login_user.php?pesan=registrasi_berhasil");
                    exit();
                } else {
                    $error = "Terjadi kesalahan: Tidak dapat terhubung ke database Firestore maupun MySQL.";
                }
            }
        } catch (Exception $e) {
            $error = "Gagal mendaftar: " . $e->getMessage();
        }
    }
}

// Ambil Departemen untuk dropdown
$departments = [];
try {
    if ($db) {
        $departments_docs = $db->collection('departments')->documents();
        foreach ($departments_docs as $doc) {
            $departments[] = $doc->data();
        }
    } else if ($conn) {
        $res = mysqli_query($conn, "SELECT * FROM departments");
        while ($row = mysqli_fetch_assoc($res)) {
            $departments[] = $row;
        }
    }
} catch (Exception $e) {
    $departments = [];
}
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>SIDIK-TI | Buat Akun Baru</title>
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
                        "secondary": "#0051d5",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#464555",
                        "surface": "#f7f9fb",
                        "surface-container-low": "#f2f4f6",
                        "surface-container-lowest": "#ffffff",
                        "outline-variant": "#c7c4d8",
                        "error-container": "#ffdad6",
                        "on-error-container": "#93000a",
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
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden">
    <main class="min-h-screen flex flex-col md:flex-row items-stretch">
        <!-- Kolom Kiri: Form Registrasi -->
        <section class="flex-1 flex items-center justify-center p-6 md:p-12 lg:p-20 bg-surface order-1">
            <div class="w-full max-w-xl space-y-10 py-12 md:py-0">
                <!-- Identitas Brand -->
                <div class="flex flex-col items-center md:items-start space-y-4">
                    <div class="flex items-center gap-3">
                        <img src="../assets/img/logo.png" alt="Logo" class="h-12 md:h-14 w-auto">
                        <h1 class="font-headline font-extrabold text-3xl tracking-tight text-indigo-900">
                            SIDIK-TI
                        </h1>
                    </div>
                    <div class="text-center md:text-left">
                        <h2 class="font-headline text-3xl font-bold text-on-surface">Buat Akun Pegawai</h2>
                        <p class="text-on-surface-variant mt-2">Daftarkan diri Anda untuk mulai mengelola infrastruktur TI.</p>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="bg-surface-container-lowest p-8 md:p-10 rounded-3xl shadow-sm shadow-indigo-500/5 border border-outline-variant/10">
                    <?php if(isset($error)): ?>
                        <div class="bg-error-container text-on-error-container p-4 rounded-2xl mb-6 flex items-center gap-3 animate-pulse">
                            <span class="material-symbols-outlined">error</span>
                            <p class="text-xs font-semibold uppercase tracking-tight"><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Username -->
                            <div class="space-y-2">
                                <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Username / NIP</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-lg">person</span>
                                    </div>
                                    <input class="block w-full pl-10 pr-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm" 
                                        name="username" placeholder="NIP Pegawai" type="text" required/>
                                </div>
                            </div>
                            <!-- Fullname -->
                            <div class="space-y-2">
                                <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Nama Lengkap</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-lg">badge</span>
                                    </div>
                                    <input class="block w-full pl-10 pr-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm" 
                                        name="fullname" placeholder="Nama Lengkap" type="text" required/>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Jabatan -->
                            <div class="space-y-2">
                                <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Jabatan</label>
                                <select name="jabatan" required class="block w-full px-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm appearance-none">
                                    <option value="Staff">Staff</option>
                                    <option value="Kepala Seksi">Kepala Seksi</option>
                                    <option value="Kepala Bidang">Kepala Bidang</option>
                                    <option value="Sekretaris">Sekretaris</option>
                                </select>
                            </div>
                            <!-- Department -->
                            <div class="space-y-2">
                                <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Departemen</label>
                                <select name="department" required class="block w-full px-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm appearance-none">
                                    <option value="">Pilih Departemen</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept['nama_dept'] ?? ''); ?>"><?php echo htmlspecialchars($dept['nama_dept'] ?? ''); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Password -->
                            <div class="space-y-2">
                                <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Password</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-lg">lock_open</span>
                                    </div>
                                    <input class="block w-full pl-10 pr-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm" 
                                        name="password" placeholder="••••••••" type="password" required/>
                                </div>
                            </div>
                            <!-- Confirm Password -->
                            <div class="space-y-2">
                                <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Konfirmasi</label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary transition-colors">
                                        <span class="material-symbols-outlined text-lg">verified_user</span>
                                    </div>
                                    <input class="block w-full pl-10 pr-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm" 
                                        name="confirm_password" placeholder="••••••••" type="password" required/>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="register" class="w-full py-4 px-6 bg-gradient-to-br from-primary to-primary-container text-white font-headline font-bold rounded-2xl shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:scale-[1.02] active:scale-95 transition-all duration-300 flex items-center justify-center gap-2">
                            <span>Daftarkan Akun Baru</span>
                            <span class="material-symbols-outlined">how_to_reg</span>
                        </button>
                    </form>
                </div>

                <div class="pt-6 text-center text-sm text-on-surface-variant">
                    <p>Sudah memiliki akses? <a class="text-primary font-bold hover:underline" href="login_user.php">Masuk di sini</a></p>
                    <p class="mt-2 text-xs opacity-60">Dengan mendaftar, Anda menyetujui protokol keamanan SIDIK-TI.</p>
                </div>
            </div>
        </section>

        <!-- Kolom Kanan: Visual Area -->
        <section class="hidden md:flex flex-1 relative overflow-hidden bg-gradient-to-br from-primary via-indigo-600 to-indigo-900 items-center justify-center p-12">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_center,_white_1.5px,_transparent_1.5px)] bg-[size:40px_40px]"></div>
            </div>
            <div class="relative z-10 max-w-md text-center text-white">
                <div class="mb-8 inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-xl rounded-[2.5rem] border border-white/20 shadow-2xl">
                    <span class="material-symbols-outlined text-4xl">inventory_2</span>
                </div>
                <h2 class="font-headline text-4xl font-extrabold leading-tight tracking-tight italic uppercase mb-4">
                    Infrastruktur <span class="bg-gradient-to-br from-white to-indigo-200 bg-clip-text text-transparent">Digital</span> Center
                </h2>
                <p class="text-white/70 font-medium leading-relaxed italic">
                    "Kelola setiap aset dengan presisi. Data Anda adalah pilar keberlanjutan operasional perusahaan."
                </p>
            </div>
        </section>
    </main>
</body>
</html>
