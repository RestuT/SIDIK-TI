<?php
ob_start();

require_once __DIR__ . '/../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

// --- LOGIKA SIMPAN PROFIL ---
$msg = "";
$user_id = $_SESSION['user_id'];

if (isset($_POST['save_profile'])) {
    $full_name        = $_POST['full_name'] ?? '';
    $username         = $_POST['username'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($db) {
        try {
            $userRef    = $db->collection('users')->document($user_id);
            $userRef->update([
                ['path' => 'full_name', 'value' => $full_name],
                ['path' => 'username',  'value' => $username]
            ]);
            $_SESSION['user'] = $username;
            if (!empty($new_password)) {
                if ($new_password === $confirm_password) {
                    $userRef->update([['path' => 'password', 'value' => password_hash($new_password, PASSWORD_DEFAULT)]]);
                    $msg = "success_pw";
                } else { $msg = "err_pw_mismatch"; }
            } else { $msg = "success"; }
        } catch (Exception $e) { $db = null; }
    }
    
    if (!$db && $conn) {
        $fn_e = mysqli_real_escape_string($conn, $full_name);
        $un_e = mysqli_real_escape_string($conn, $username);
        $sql = "UPDATE users SET full_name = '$fn_e', username = '$un_e' WHERE id = '$user_id'";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['user'] = $username;
            if (!empty($new_password)) {
                if ($new_password === $confirm_password) {
                    $pw = password_hash($new_password, PASSWORD_DEFAULT);
                    mysqli_query($conn, "UPDATE users SET password = '$pw' WHERE id = '$user_id'");
                    $msg = "success_pw";
                } else { $msg = "err_pw_mismatch"; }
            } else { $msg = "success"; }
        } else { $msg = "error_db"; }
    }
}

// --- AMBIL DATA PROFIL TERBARU ---
$current_admin = [];
if ($db) {
    try {
        $userSnap = $db->collection('users')->document($user_id)->snapshot();
        if ($userSnap->exists()) $current_admin = $userSnap->data();
    } catch (Exception $e) { $db = null; }
}
if (!$db && $conn) {
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
    if ($res) { $current_admin = mysqli_fetch_assoc($res); }
}
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | System Settings';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-surface-container-low font-body text-on-surface antialiased overflow-x-hidden min-h-screen">
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>
    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 md:px-8 py-4 md:py-5 border-b border-outline-variant/10 sticky top-0 bg-white/80 backdrop-blur-xl z-20">
            <div>
                <h1 class="font-headline text-xl md:text-2xl font-extrabold text-on-surface tracking-tight italic uppercase leading-none">System <span class="text-primary italic md:text-3xl">Settings</span></h1>
                <p class="text-[10px] text-on-surface-variant font-black uppercase tracking-[0.2em] mt-1">Configuration &amp; Preferences</p>
            </div>
            <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-100">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Settings Synced</span>
            </div>
        </header>

        <div class="p-4 md:p-8 max-w-5xl mx-auto w-full space-y-8 md:space-y-10">
            <?php if($msg === 'success' || $msg === 'success_pw'): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-8 py-5 rounded-3xl flex items-center gap-4 shadow-sm">
                    <span class="material-symbols-outlined text-2xl fill-1">check_circle</span>
                    <div class="flex flex-col">
                        <p class="font-headline font-bold text-sm uppercase">Berhasil Disimpan!</p>
                        <p class="text-[10px] font-medium opacity-70 mt-1"><?php echo ($msg === 'success_pw') ? 'Password telah diperbarui.' : 'Profil diperbarui secara real-time.'; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <section class="space-y-6">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-2xl">account_circle</span>
                    <h2 class="font-headline text-xl font-bold">Profil Admin</h2>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST" class="bg-white rounded-[3rem] p-10 border border-outline-variant/10 shadow-sm space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant ml-1">Nama Lengkap</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($current_admin['full_name'] ?? ''); ?>" required class="w-full bg-surface-container-low border-0 rounded-2xl p-4 font-bold text-sm outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant ml-1">Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($current_admin['username'] ?? ''); ?>" required class="w-full bg-surface-container-low border-0 rounded-2xl p-4 font-bold text-sm outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant ml-1">Password Baru</label>
                            <input type="password" name="new_password" placeholder="••••••••" class="w-full bg-surface-container-low border-0 rounded-2xl p-4 font-bold text-sm outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-on-surface-variant ml-1">Konfirmasi Password</label>
                            <input type="password" name="confirm_password" placeholder="••••••••" class="w-full bg-surface-container-low border-0 rounded-2xl p-4 font-bold text-sm outline-none">
                        </div>
                    </div>
                    <div class="pt-6 border-t border-outline-variant/10 flex justify-end">
                        <button type="submit" name="save_profile" class="px-8 py-4 bg-primary text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl hover:scale-105 transition active:scale-95">Update Profil Saya</button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
