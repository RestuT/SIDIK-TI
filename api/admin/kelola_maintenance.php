<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login_user.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard_admin.php");
    exit();
}

$id = $_GET['id'];
$data = null;
$technicians = [];

if ($db) {
    try {
        $submissionRef = $db->collection('submissions')->document($id);
        $snap = $submissionRef->snapshot();
        if ($snap->exists()) {
            $data = $snap->data();
            $data['id'] = $snap->id();
            if (($data['type'] ?? '') === 'Maintenance') {
                $uRef = $db->collection('users')->document($data['user_id'] ?? '');
                $uSnap = $uRef->snapshot();
                $uData = $uSnap->exists() ? $uSnap->data() : [];
                $data['pemohon'] = $uData['full_name'] ?? 'Unknown';
                $data['department'] = $uData['department'] ?? 'Unknown';
                $data['jabatan'] = $uData['jabatan'] ?? 'Unknown';

                $tech_docs = $db->collection('users')->where('role', 'in', ['staff', 'admin', 'technician'])->documents();
                foreach ($tech_docs as $doc) {
                    $t = $doc->data();
                    $t['id'] = $doc->id();
                    $technicians[] = $t;
                }
            }
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $id_sql = mysqli_real_escape_string($conn, $id);
    $res = mysqli_query($conn, "SELECT s.*, u.full_name as pemohon, u.department, u.jabatan FROM submissions s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = '$id_sql' AND s.type = 'Maintenance'");
    $data = mysqli_fetch_assoc($res);
    if ($data) {
        $t_res = mysqli_query($conn, "SELECT id, full_name, role FROM users WHERE role IN ('staff', 'admin', 'technician')");
        while ($row = mysqli_fetch_assoc($t_res)) {
            $technicians[] = $row;
        }
    }
}

if (!$data) {
    die("Data maintenance tidak ditemukan.");
}

if (isset($_POST['update_maintenance'])) {
    require_csrf_token();
    $status = $_POST['status'];
    $pic_id = !empty($_POST['pic_id']) ? $_POST['pic_id'] : null;
    $note   = $_POST['admin_note'];

    if ($db) {
        try {
            $db->collection('submissions')->document($id)->update([
                ['path' => 'status', 'value' => $status],
                ['path' => 'pic_id', 'value' => $pic_id],
                ['path' => 'admin_reasoning', 'value' => $note]
            ]);
        } catch (Exception $e) {}
    } else if ($conn) {
        $status_sql = mysqli_real_escape_string($conn, $status);
        $pic_sql = !empty($pic_id) ? "'" . mysqli_real_escape_string($conn, $pic_id) . "'" : "NULL";
        $note_sql = mysqli_real_escape_string($conn, $note);
        $id_sql = mysqli_real_escape_string($conn, $id);
        mysqli_query($conn, "UPDATE submissions SET status = '$status_sql', pic_id = $pic_sql, admin_reasoning = '$note_sql' WHERE id = '$id_sql'");
    }
    header("Location: dashboard_admin.php?status=success");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Maintenance - SIDIK-TI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-100 font-sans antialiased overflow-x-hidden min-h-screen">
    <?php include __DIR__ . '/../includes/navbar_admin.php'; ?>
    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen flex flex-col">
        <div class="max-w-4xl mx-auto p-6 md:p-10">
            <a href="dashboard_admin.php" class="inline-flex items-center text-sm font-bold text-gray-500 hover:text-blue-600 mb-6 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Dashboard
            </a>
            <div class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-emerald-600 p-8 text-white flex justify-between items-center text-sm font-black uppercase tracking-widest opacity-80">
                    <div>
                        <p>Tiket Maintenance</p>
                        <h2 class="text-3xl font-black italic mt-1">#<?php echo htmlspecialchars($data['ticket_number'] ?? ''); ?></h2>
                    </div>
                    <div class="bg-white/20 px-6 py-2 rounded-full backdrop-blur-md">
                        <span><?php echo htmlspecialchars($data['status'] ?? ''); ?></span>
                    </div>
                </div>
                <div class="p-10 grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Informasi Pelapor</h4>
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 text-sm font-bold text-gray-800">
                                <p><?php echo htmlspecialchars($data['pemohon']); ?></p>
                                <p class="text-xs text-gray-500 font-normal"><?php echo htmlspecialchars($data['department']); ?></p>
                            </div>
                        </section>
                        <section>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Item & Keluhan</h4>
                            <div class="space-y-3">
                                <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($data['title']); ?></p>
                                <div class="bg-emerald-50 p-5 rounded-2xl border border-emerald-100 italic text-sm text-gray-700">
                                    <p class="text-xs font-bold text-emerald-700 underline mb-2 not-italic">Masalah:</p>
                                    <?php echo nl2br(htmlspecialchars($data['description'])); ?>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div class="bg-slate-50 p-8 rounded-[35px] border border-slate-100 h-fit">
                        <form action="" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Status</label>
                                <select name="status" class="w-full p-4 bg-white border border-gray-200 rounded-2xl font-black text-sm outline-none focus:ring-4 focus:ring-emerald-100 transition">
                                    <option value="Menunggu" <?php echo ($data['status'] == 'Menunggu') ? 'selected' : ''; ?>>Menunggu</option>
                                    <option value="Proses" <?php echo ($data['status'] == 'Proses') ? 'selected' : ''; ?>>Dalam Perbaikan</option>
                                    <option value="Selesai" <?php echo ($data['status'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="Ditolak" <?php echo ($data['status'] == 'Ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Technician (PIC)</label>
                                <select name="pic_id" class="w-full p-4 bg-white border border-gray-200 rounded-2xl font-black text-sm outline-none focus:ring-4 focus:ring-emerald-100 transition">
                                    <option value="">-- No PIC Assigned --</option>
                                    <?php foreach($technicians as $t): ?>
                                        <option value="<?php echo $t['id']; ?>" <?php echo (($data['pic_id'] ?? '') == $t['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2 ml-1">Admin Notes</label>
                                <textarea name="admin_note" rows="4" class="w-full p-4 bg-white border border-gray-200 rounded-2xl text-sm outline-none focus:ring-4 focus:ring-emerald-100 transition"><?php echo htmlspecialchars($data['admin_reasoning'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_maintenance" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 rounded-2xl shadow-lg transition transform active:scale-95 uppercase tracking-widest text-xs">
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
