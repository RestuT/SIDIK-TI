<?php
session_start();
include '../config/database.php';

if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $dept     = mysqli_real_escape_string($conn, $_POST['department']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // 1. Validasi Password
    if ($password !== $confirm) {
        $error = "Konfirmasi password tidak sesuai!";
    } else {
        // 2. Cek apakah username sudah ada
        $check_user = mysqli_query($conn, "SELECT username FROM users WHERE username = '$username'");
        if (mysqli_num_rows($check_user) > 0) {
            $error = "Username sudah terdaftar!";
        } else {
            // 3. Enkripsi Password (Penting untuk keamanan)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 4. Insert ke Database
            $query = "INSERT INTO users (username, password, full_name, department, role) 
                      VALUES ('$username', '$hashed_password', '$fullname', '$dept', 'staff')";

            if (mysqli_query($conn, $query)) {
                header("Location: login_user.php?pesan=registrasi_berhasil");
                exit();
            } else {
                $error = "Gagal mendaftar: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - IT Helpdesk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center font-sans p-6">

    <div class="bg-white p-8 rounded-3xl shadow-xl w-full max-w-lg border border-gray-100">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Buat Akun Baru</h2>
            <p class="text-gray-500 text-sm mt-2">Daftarkan diri Anda untuk mengakses layanan IT Helpdesk</p>
        </div>

        <?php if(isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700 text-sm"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username / NIP</label>
                    <input type="text" name="username" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Username">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="fullname" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Nama Lengkap">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Departemen</label>
                <select name="department" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="Humas">Humas</option>
                    <option value="Keuangan">Keuangan</option>
                    <option value="Teknologi">Teknologi</option>
                    <option value="SDM">SDM</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Kata Sandi</label>
                    <input type="password" name="password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Konfirmasi Sandi</label>
                    <input type="password" name="confirm_password" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••">
                </div>
            </div>

            <button type="submit" name="register" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl shadow-lg transition transform active:scale-95">
                Daftar Akun
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500">Sudah punya akun? <a href="login_user.php" class="text-blue-600 font-bold hover:underline">Masuk di sini</a></p>
        </div>
    </div>

</body>
</html>