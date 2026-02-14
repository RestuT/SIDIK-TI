<?php
session_start();
include '../config/database.php'; // Pastikan path ke database.php benar

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // 1. Query mencari user berdasarkan username
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $data_user = mysqli_fetch_assoc($result);

        // 2. Verifikasi Password (menggunakan password_verify jika dipassword_hash)
        // Jika masih menggunakan plain text (tidak disarankan), gunakan: if($password === $data_user['password'])
        if (password_verify($password, $data_user['password'])) {
            
            // 3. Simpan data ke Session agar Foreign Key di submissions valid
            $_SESSION['user'] = $data_user['username'];
            $_SESSION['user_id'] = $data_user['id']; // INI KUNCI AGAR TIDAK ERROR FOREIGN KEY
            $_SESSION['role'] = $data_user['role'];

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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi User - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center font-sans">

    <div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-md border border-gray-100">
        <div class="text-center mb-8">
            <div class="bg-blue-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-200">
                <i class="fa-solid fa-user-shield text-white text-2xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Validasi Akses</h2>
            <p class="text-gray-500 text-sm mt-2">Silakan masuk untuk melakukan pengajuan</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700 text-sm"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Username / NIP</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa-solid fa-id-card"></i>
                    </span>
                    <input type="text" name="username" required 
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition outline-none" 
                        placeholder="Masukkan ID Anda">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Kata Sandi</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" required 
                        class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:bg-white transition outline-none" 
                        placeholder="••••••••">
                </div>
            </div>

            <button type="submit" name="login" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-blue-100 transition transform active:scale-95">
                Masuk ke Sistem
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-500">
                Belum punya akun? 
                <a href="register_user.php" class="text-blue-600 font-bold hover:underline">Daftar Sekarang</a>
            </p>
            <p class="text-sm text-gray-500">
                Kembali ke <a href="../index.php" class="text-blue-600 font-bold hover:underline">Halaman Utama</a>
            </p> 
        </div>
    </div>

</body>
</html>