<?php
ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/csrf_helper.php';

// Proteksi Teknisi
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'technician' && $_SESSION['role'] !== 'admin')) {
    header("Location: ../auth/login_user.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard_technician.php");
    exit();
}

$id = $_GET['id'];
$tech_id = $_SESSION['user_id'];
$data = null;

if ($db) {
    try {
        $submissionRef = $db->collection('submissions')->document($id);
        $snap = $submissionRef->snapshot();
        if ($snap->exists()) {
            $data = $snap->data();
            $data['id'] = $snap->id();
            if ($_SESSION['role'] !== 'admin' && ($data['pic_id'] ?? '') !== $tech_id) {
                die("Akses ditolak.");
            }
            $uSnap = $db->collection('users')->document($data['user_id'] ?? '')->snapshot();
            $uData = $uSnap->exists() ? $uSnap->data() : [];
            $data['pemohon'] = $uData['full_name'] ?? 'Unknown';
            $data['department'] = $uData['department'] ?? 'Unknown';
        }
    } catch (Exception $e) { $db = null; }
}

if (!$db && $conn) {
    $id_sql = mysqli_real_escape_string($conn, $id);
    $res = mysqli_query($conn, "SELECT s.*, u.full_name as pemohon, u.department FROM submissions s LEFT JOIN users u ON s.user_id = u.id WHERE s.id = '$id_sql' AND s.type = 'Pengadaan'");
    $data = mysqli_fetch_assoc($res);
    if ($data && $_SESSION['role'] !== 'admin' && ($data['pic_id'] ?? '') != $tech_id) {
        die("Akses ditolak.");
    }
}

if (!$data) {
    die("Data pengadaan tidak ditemukan.");
}

if (isset($_POST['update_tech'])) {
    require_csrf_token();
    $status = $_POST['status'];
    $note   = $_POST['tech_note'];

    if ($db) {
        try {
            $db->collection('submissions')->document($id)->update([
                ['path' => 'status', 'value' => $status],
                ['path' => 'admin_reasoning', 'value' => $note]
            ]);
        } catch (Exception $e) {}
    } else if ($conn) {
        $status_sql = mysqli_real_escape_string($conn, $status);
        $note_sql = mysqli_real_escape_string($conn, $note);
        $id_sql = mysqli_real_escape_string($conn, $id);
        mysqli_query($conn, "UPDATE submissions SET status = '$status_sql', admin_reasoning = '$note_sql' WHERE id = '$id_sql'");
    }
    header("Location: dashboard_technician.php?status=updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Process Procurement - #<?php echo htmlspecialchars($data['ticket_number']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <script>
        // Inject base tag to preserve relative link resolution
        if (!document.querySelector('base')) {
            var base = document.createElement('base');
            base.href = window.location.href.split('?')[0];
            document.head.appendChild(base);
        }
        // Mask URL to Pretty Path
        if (window.history.replaceState) {
            var path = window.location.pathname;
            var search = window.location.search;
            if (path.includes('/api/')) {
                window.history.replaceState(null, null, path.replace('/api/', '/') + search);
            }
        }
    </script>
</head>
<body class="bg-indigo-50/30 font-sans antialiased min-h-screen">
    <?php include __DIR__ . '/../includes/navbar_technician.php'; ?>
    <main class="lg:ml-72 pt-14 lg:pt-0 min-h-screen tech-layout">
        <div class="max-w-4xl mx-auto p-6 md:p-10">
            <a href="dashboard_technician.php" class="inline-flex items-center text-sm font-bold text-indigo-500 hover:text-indigo-800 mb-6 transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Kembali ke Antrean
            </a>
            <div class="bg-white rounded-[40px] shadow-sm border border-indigo-100 overflow-hidden">
                <div class="bg-indigo-900 p-8 text-white flex justify-between items-center text-sm font-black uppercase tracking-widest opacity-60">
                    <div>
                        <p>Procurement Process</p>
                        <h2 class="text-3xl font-black italic mt-1">#<?php echo htmlspecialchars($data['ticket_number']); ?></h2>
                    </div>
                    <div class="bg-white/10 px-6 py-2 rounded-full border border-white/20">
                        <span><?php echo strtoupper($data['status']); ?></span>
                    </div>
                </div>
                <div class="p-10 grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-6">
                        <section>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Pemohon</h4>
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 text-sm font-bold text-gray-800">
                                <p><?php echo htmlspecialchars($data['pemohon']); ?></p>
                                <p class="text-[10px] text-gray-500 font-bold uppercase"><?php echo htmlspecialchars($data['department']); ?></p>
                            </div>
                        </section>
                        <section>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">Item Pengadaan</h4>
                            <p class="text-lg font-black text-indigo-950"><?php echo htmlspecialchars($data['title']); ?></p>
                            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100 italic text-sm text-gray-600">
                                <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-2 not-italic">Justifikasi:</p>
                                <?php echo nl2br(htmlspecialchars($data['description'])); ?>
                            </div>
                        </section>
                    </div>
                    <div class="bg-indigo-50/50 p-8 rounded-[35px] border border-indigo-100 h-fit">
                        <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div>
                                <label class="block text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-2 ml-1">Status Barang / Proses</label>
                                <select name="status" class="w-full p-4 bg-white border border-indigo-100 rounded-2xl font-black text-sm outline-none focus:ring-4 focus:ring-indigo-200 transition">
                                    <option value="Menunggu" <?php echo $data['status'] == 'Menunggu' ? 'selected' : ''; ?>>Dalam Antrean</option>
                                    <option value="Proses" <?php echo $data['status'] == 'Proses' ? 'selected' : ''; ?>>Pemesanan / Distribusi</option>
                                    <option value="Selesai" <?php echo $data['status'] == 'Selesai' ? 'selected' : ''; ?>>Diterima User</option>
                                    <option value="Ditolak" <?php echo $data['status'] == 'Ditolak' ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-2 ml-1">Notes & S/N</label>
                                <textarea name="tech_note" rows="5" class="w-full p-4 bg-white border border-indigo-100 rounded-2xl text-sm italic outline-none focus:ring-4 focus:ring-indigo-200 transition"><?php echo htmlspecialchars($data['admin_reasoning'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" name="update_tech" class="w-full bg-indigo-900 hover:bg-black text-white font-black py-4 rounded-2xl shadow-lg transition transform active:scale-95 uppercase tracking-widest text-[10px]">
                                Submit Update
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

