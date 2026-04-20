<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_admin.php");
    exit();
}

if (isset($_POST['register_tech'])) {
    require_csrf_token();
    
    $username = $_POST['username'];
    $fullname = $_POST['fullname'];
    $dept     = $_POST['department'];
    $jabatan  = $_POST['jabatan'];
    $password = $_POST['password'];
    $role     = $_POST['role']; // Can be technician or staff

    try {
        // 1. Cek apakah username sudah ada
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
            $error = "Username/NIP sudah terdaftar!";
        } else {
            // 2. Enkripsi Password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 3. Insert data
            $saved = false;
            if ($db) { // Firestore
                $db->collection('users')->add([
                    'username' => $username,
                    'password' => $hashed_password,
                    'full_name' => $fullname,
                    'department' => $dept,
                    'jabatan' => $jabatan,
                    'role' => $role, 
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $saved = true;
            } else if ($conn) { // MySQL
                $username_esc = mysqli_real_escape_string($conn, $username);
                $fullname_esc = mysqli_real_escape_string($conn, $fullname);
                $dept_esc = mysqli_real_escape_string($conn, $dept);
                $jabatan_esc = mysqli_real_escape_string($conn, $jabatan);
                $role_esc = mysqli_real_escape_string($conn, $role);
                
                $sql = "INSERT INTO users (username, password, full_name, department, jabatan, role, created_at) 
                        VALUES ('$username_esc', '$hashed_password', '$fullname_esc', '$dept_esc', '$jabatan_esc', '$role_esc', NOW())";
                if (mysqli_query($conn, $sql)) {
                    $saved = true;
                }
            }

            if ($saved) {
                header("Location: manage_users.php?status=tech_created");
                exit();
            } else {
                $error = "Terjadi kesalahan database.";
            }
        }
    } catch (Exception $e) {
        $error = "Gagal mendaftar: " . $e->getMessage();
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
    <title>SIDIK-TI | Register Technician</title>
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
</head>
<body class="bg-surface font-body text-on-surface antialiased overflow-x-hidden min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>

    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex items-center justify-center p-6 md:p-12">
        <div class="w-full max-w-2xl space-y-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="manage_users.php" class="w-10 h-10 rounded-xl bg-surface-container-low flex items-center justify-center text-outline hover:text-primary transition-all">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <h1 class="font-headline text-2xl font-black text-on-surface uppercase tracking-tight italic">Register <span class="text-primary italic">Privileged User</span></h1>
                    <p class="text-[10px] text-outline font-black uppercase tracking-widest leading-none mt-1">Tambahkan personil teknis atau struktural baru ke sistem</p>
                </div>
            </div>

            <div class="bg-white p-8 md:p-10 rounded-[2.5rem] shadow-2xl shadow-indigo-900/5 border border-outline-variant/10">
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
                            <input class="block w-full px-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm font-bold" 
                                name="username" placeholder="Masukkan NIP" type="text" required/>
                        </div>
                        <!-- Fullname -->
                        <div class="space-y-2">
                            <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Nama Lengkap</label>
                            <input class="block w-full px-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm font-bold" 
                                name="fullname" placeholder="Nama Lengkap" type="text" required/>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Role Selection -->
                        <div class="space-y-2">
                            <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Role Akun</label>
                            <select name="role" required class="block w-full px-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm font-bold appearance-none">
                                <option value="technician">Technician (Field Specialist)</option>
                                <option value="staff">Staff (Administrative)</option>
                                <option value="head">Kepala Departemen (Department Head)</option>
                            </select>
                        </div>
                        <!-- Department -->
                        <div class="space-y-2">
                            <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Departemen</label>
                            <select name="department" required class="block w-full px-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm font-bold appearance-none">
                                <option value="">Pilih Departemen</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['nama_dept'] ?? ''); ?>"><?php echo htmlspecialchars($dept['nama_dept'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Jabatan -->
                        <div class="space-y-2">
                            <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Jabatan Spesifik</label>
                            <input class="block w-full px-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm font-bold" 
                                name="jabatan" placeholder="Contoh: Teknisi Jaringan" type="text" required/>
                        </div>
                        <!-- Password Initial -->
                        <div class="space-y-2">
                            <label class="block font-headline text-xs font-bold text-on-surface-variant uppercase tracking-widest ml-1">Initial Password</label>
                            <input class="block w-full px-4 py-3.5 bg-surface-container-low border-0 rounded-2xl focus:ring-2 focus:ring-primary/20 focus:bg-white transition-all outline-none text-sm font-bold" 
                                name="password" placeholder="••••••••" type="password" required/>
                        </div>
                    </div>

                    <button type="submit" name="register_tech" class="w-full py-4 bg-gradient-to-br from-primary to-primary-container text-white font-headline font-bold rounded-2xl shadow-lg shadow-primary/30 hover:shadow-primary/50 hover:scale-[1.02] active:scale-95 transition-all duration-300 flex items-center justify-center gap-2 uppercase tracking-widest text-xs">
                        <span>Simpan Data Personil</span>
                        <span class="material-symbols-outlined">verified_user</span>
                    </button>
                </form>
            </div>
        </div>
    </main>

</body>
</html>
