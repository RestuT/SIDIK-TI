<?php
session_start();
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
                if ($db) { // Firestore
                    $db->collection('users')->add([
                        'username' => $username,
                        'password' => $hashed_password,
                        'full_name' => $fullname,
                        'role' => 'admin',
                        'two_fa_code' => $two_fa,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } else if ($conn) { // MySQL
                    $username = mysqli_real_escape_string($conn, $username);
                    $fullname = mysqli_real_escape_string($conn, $fullname);
                    $two_fa = mysqli_real_escape_string($conn, $two_fa);
                    
                    $sql = "INSERT INTO users (username, password, full_name, role, two_fa_code, created_at) 
                            VALUES ('$username', '$hashed_password', '$fullname', 'admin', '$two_fa', NOW())";
                    mysqli_query($conn, $sql);
                }
                
                header("Location: login_admin.php?pesan=reg_sukses");
                exit();
            }
        } catch (Exception $e) {
            $error = "Gagal mendaftar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Admin - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include_once __DIR__ . '/../includes/firebase_js.php'; ?>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-6">

    <div class="bg-white p-8 rounded-[35px] shadow-2xl w-full max-w-lg">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-slate-800">Registrasi Administrator</h2>
            <p class="text-slate-500 text-sm">Pastikan simpan kode 2FA Anda dengan aman</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 text-sm font-bold border-l-4 border-red-500">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Username</label>
                    <input type="text" name="username" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Username">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Nama Lengkap</label>
                    <input type="text" name="fullname" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Nama Lengkap">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Password</label>
                    <input type="password" name="password" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Konfirmasi</label>
                    <input type="password" name="confirm_password" required class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="••••••••">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1 tracking-wider">Setup Kode 2FA (6 Digit PIN)</label>
                <input type="text" name="two_fa_code" maxlength="6" required placeholder="Contoh: 123456" 
                    class="w-full p-3 bg-blue-50 border border-blue-100 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-center text-xl tracking-widest">
            </div>

            <button type="submit" name="register_admin" class="w-full bg-slate-900 hover:bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg transition-all active:scale-95">
                Daftarkan Admin Baru
            </button>
        </form>

        <p class="text-center mt-6 text-sm text-slate-500">
            Sudah punya akun? <a href="login_admin.php" class="text-blue-600 font-bold hover:underline">Masuk di sini</a>
        </p>
    </div>

</body>
</html>