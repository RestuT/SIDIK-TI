<?php
session_start();
// PERBAIKAN: Pastikan file database.php benar-benar terhubung
include '../config/database.php'; 

$step = 1; 
$error = "";

// Tahap 1: Validasi Username & Password
if (isset($_POST['login_step1'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Hanya mencari user dengan role admin
    $query = "SELECT * FROM users WHERE username = '$username' AND role = 'admin'";
    $result = mysqli_query($conn, $query);

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

    $code = mysqli_real_escape_string($conn, $_POST['two_fa_code']);
    $admin_id = $_SESSION['temp_admin_id'];

    $query = "SELECT * FROM users WHERE id = '$admin_id' AND two_fa_code = '$code'";
    $result = mysqli_query($conn, $query);

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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Login 2FA - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center font-sans p-6">

    <div class="bg-white p-10 rounded-[40px] shadow-2xl w-full max-w-md border border-slate-800">
        <div class="text-center mb-8">
            <div class="bg-blue-600 w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/50">
                <i class="fa-solid fa-user-shield text-white text-3xl"></i>
            </div>
            <h2 class="text-3xl font-black text-slate-800 tracking-tight italic">ADMIN <span class="text-blue-600">SECURE</span></h2>
            <p class="text-slate-500 text-sm mt-2 font-medium tracking-tighter uppercase">IT Helpdesk - Restu Utami</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl animate-bounce">
                <p class="text-red-700 text-[10px] font-black uppercase tracking-widest"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <?php if($step == 1): ?>
            <form action="" method="POST" class="space-y-6">
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">ID Administrator</label>
                    <input type="text" name="username" required autocomplete="off" class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all outline-none font-bold text-slate-700" placeholder="Username">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all outline-none font-bold text-slate-700" placeholder="••••••••">
                </div>
                <button type="submit" name="login_step1" class="w-full bg-slate-900 hover:bg-blue-600 text-white font-black py-4 rounded-2xl shadow-xl transition-all active:scale-95 uppercase tracking-widest">
                    Lanjut Ke Verifikasi
                </button>
                                <a href="register_admin.php" class="block text-center text-xs text-slate-400 hover:text-slate-600 transition font-bold uppercase">Daftar Admin Baru</a>
                                <a href="../index.php" class="block text-center text-xs text-slate-400 hover:text-slate-600 transition font-bold uppercase">Kembali ke Menu Utama</a>
            </form>
        <?php else: ?>
            <form action="" method="POST" class="space-y-6">
                <div class="text-center mb-4">
                    <p class="text-xs text-slate-400 uppercase font-bold tracking-widest mb-1">Identitas Terverifikasi</p>
                    <p class="text-sm text-slate-800 font-black">Halo, <?php echo $_SESSION['temp_admin_user']; ?>!</p>
                </div>
                <div>
                    <label class="block text-center text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Input 6-Digit PIN 2FA</label>
                    <input type="text" name="two_fa_code" maxlength="6" required autofocus class="w-full text-center text-4xl tracking-[1rem] py-4 bg-blue-50 border-2 border-blue-200 rounded-2xl focus:border-blue-500 transition-all outline-none font-black text-blue-700" placeholder="000000">
                </div>
                <button type="submit" name="verify_2fa" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl shadow-xl transition-all active:scale-95 uppercase tracking-widest">
                    Buka Dashboard
                </button>
                <a href="login_admin.php" class="block text-center text-xs text-slate-400 hover:text-slate-600 transition font-bold uppercase">Kembali</a>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>