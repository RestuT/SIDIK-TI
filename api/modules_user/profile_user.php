<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login_user.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$status = '';

// Proses Update Profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    require_csrf_token();
    
    $full_name = trim($_POST['full_name']);
    $new_password = $_POST['new_password'];
    
    if ($db) {
        try {
            $updateData = [];
            if (!empty($full_name)) {
                $updateData[] = ['path' => 'full_name', 'value' => $full_name];
                $_SESSION['full_name'] = $full_name;
            }
            if (!empty($new_password)) {
                $updateData[] = ['path' => 'password', 'value' => password_hash($new_password, PASSWORD_BCRYPT)];
            }
            if (!empty($updateData)) {
                $db->collection('users')->document($user_id)->update($updateData);
                $status = 'success'; $message = 'Profil berhasil diperbarui.';
            }
        } catch (Exception $e) { $db = null; }
    }

    if (!$db && $conn) {
        $uid_e = mysqli_real_escape_string($conn, $user_id);
        $full_name_e = mysqli_real_escape_string($conn, $full_name);
        $sql = "UPDATE users SET full_name = '$full_name_e'";
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_BCRYPT);
            $hashed_e = mysqli_real_escape_string($conn, $hashed);
            $sql .= ", password = '$hashed_e'";
        }
        $sql .= " WHERE id = '$uid_e'";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['full_name'] = $full_name;
            $status = 'success'; $message = 'Profil berhasil diperbarui.';
        } else {
            $status = 'error'; $message = 'Terjadi kesalahan saat memperbarui profil.';
        }
    }
}

// Fetch Profil Current
$user_data = [];
if ($db) {
    try {
        $userSnap = $db->collection('users')->document($user_id)->snapshot();
        $user_data = $userSnap->exists() ? $userSnap->data() : [];
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $uid_e = mysqli_real_escape_string($conn, $user_id);
    $res = mysqli_query($conn, "SELECT * FROM users WHERE id = '$uid_e' LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) $user_data = $row;
}

$display_name = $user_data['full_name'] ?? 'User';
$username = $user_data['username'] ?? '-';
$department = $user_data['department'] ?? '-';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php 
        $pageTitle = 'SIDIK-TI | Profile User';
        $base_url = '../';
        include __DIR__ . '/../includes/head_meta.php'; 
    ?>
</head>
<body class="bg-background text-on-surface min-h-screen selection:bg-primary/20 pb-24 md:pb-0 transition-colors duration-300">
    <?php include __DIR__ . '/../includes/navbar_user.php'; ?>
    <main class="max-w-3xl mx-auto px-6 md:px-10 py-12 space-y-8">
        <div class="text-center space-y-2 mb-10">
            <h1 class="text-4xl font-black font-headline tracking-tight text-slate-900 italic">Informasi <span class="text-primary italic">Profil</span></h1>
            <p class="text-slate-500 font-medium">Kelola data personal dan keamanan sandi Anda.</p>
        </div>

        <?php if($status === 'success'): ?>
            <div class="bg-emerald-100 border border-emerald-200 text-emerald-700 px-6 py-4 rounded-2xl flex items-center gap-3 font-bold text-sm shadow-sm"><span class="material-symbols-outlined fill-1 text-xl">check_circle</span><?php echo $message; ?></div>
        <?php elseif($status === 'error'): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-6 py-4 rounded-2xl flex items-center gap-3 font-bold text-sm shadow-sm"><span class="material-symbols-outlined fill-1 text-xl">error</span><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="bg-white rounded-[2.5rem] shadow-xl shadow-indigo-100/50 border border-slate-100 overflow-hidden relative">
            <div class="bg-gradient-to-br from-indigo-50 to-white px-8 py-10 border-b border-indigo-50 flex flex-col md:flex-row items-center gap-6">
                <div class="w-24 h-24 bg-primary text-white font-headline font-black text-4xl rounded-[2rem] flex items-center justify-center shadow-lg shadow-primary/30 uppercase"><?php echo substr($display_name, 0, 1); ?></div>
                <div class="text-center md:text-left space-y-1 z-10">
                    <h2 class="text-2xl font-black font-headline text-slate-800"><?php echo htmlspecialchars($display_name); ?></h2>
                    <p class="text-primary font-bold text-sm uppercase tracking-wider"><?php echo htmlspecialchars($department); ?></p>
                </div>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST" class="p-8 space-y-8 relative z-10">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Username (Login ID)</label>
                        <input type="text" value="<?php echo htmlspecialchars($username); ?>" disabled class="block w-full px-6 py-4 bg-slate-50 border-0 rounded-2xl font-bold text-slate-500 outline-none cursor-not-allowed">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Nama Lengkap</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($display_name); ?>" required class="block w-full px-6 py-4 bg-surface-container-low border-0 rounded-2xl font-bold text-slate-800 outline-none focus:ring-2 focus:ring-primary/20 transition-all">
                    </div>
                </div>
                <div class="pt-6 border-t border-slate-100">
                    <h3 class="text-lg font-black font-headline text-slate-800 flex items-center gap-2 mb-6"><span class="material-symbols-outlined text-primary">lock</span>Keamanan</h3>
                    <div class="space-y-2 max-w-md">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Kata Sandi Baru</label>
                        <input type="password" name="new_password" placeholder="Kosongkan jika tidak ingin mengubah" class="block w-full px-6 py-4 bg-surface-container-low border-0 outline-none focus:ring-2 focus:ring-primary/20 rounded-2xl font-medium text-slate-800 transition-all font-mono">
                    </div>
                </div>
                <div class="pt-6 flex flex-col sm:flex-row gap-4 items-center justify-end">
                    <button type="submit" name="update_profile" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-br from-indigo-600 to-indigo-500 text-white font-black rounded-2xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 active:scale-95 transition-all text-sm uppercase tracking-widest flex items-center justify-center gap-2 group/btn"><span class="material-symbols-outlined text-lg">save</span>Simpan Perubahan</button>
                    <a href="../auth/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" class="w-full sm:w-auto px-8 py-4 bg-red-50 text-red-600 font-black rounded-2xl hover:bg-red-100 active:scale-95 transition-all text-sm uppercase tracking-widest flex items-center justify-center gap-2"><span class="material-symbols-outlined text-lg">logout</span>Keluar Akun</a>
                </div>
            </form>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/bottom_nav_user.php'; ?>
</body>
</html>
