<?php
session_start();
include '../config/database.php';
include '../config/csrf_helper.php';

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
            // 2. Cek apakah username sudah ada di Firestore
            $userRef = $db->collection('users')->where('username', '=', $username)->limit(1)->documents();
            
            if (!$userRef->isEmpty()) {
                $error = "Username sudah terdaftar!";
            } else {
                // 3. Enkripsi Password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 4. Insert ke Firestore
                $db->collection('users')->add([
                    'username' => $username,
                    'password' => $hashed_password,
                    'full_name' => $fullname,
                    'department' => $dept,
                    'jabatan' => $jabatan,
                    'role' => 'user', // Default role
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                header("Location: login_user.php?pesan=registrasi_berhasil");
                exit();
            }
        } catch (Exception $e) {
            $error = "Gagal mendaftar: " . $e->getMessage();
        }
    }
}

// Ambil Departemen dari Firestore untuk dropdown
try {
    $departments_docs = $db->collection('departments')->documents();
    $departments = [];
    foreach ($departments_docs as $doc) {
        $departments[] = $doc->data();
    }
} catch (Exception $e) {
    $departments = [];
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
    <?php include_once '../includes/firebase_js.php'; ?>
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
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Jabatan</label>
        <select name="jabatan" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="Staff">Staff</option>
            <option value="Kepala Seksi">Kepala Seksi</option>
            <option value="Kepala Bidang">Kepala Bidang</option>
            <option value="Sekretaris">Sekretaris</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Departemen</label>
        <select name="department" required class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="">-- Pilih Departemen --</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?php echo htmlspecialchars($dept['nama_dept'] ?? ''); ?>"><?php echo htmlspecialchars($dept['nama_dept'] ?? ''); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
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