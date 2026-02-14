<?php
session_start();
include '../config/database.php';

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, u.full_name FROM submissions s JOIN users u ON s.user_id = u.id WHERE s.id = $id"));

// Ambil daftar teknisi untuk pilihan PIC
$technicians = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role = 'technician' OR role = 'admin'");

if (isset($_POST['update'])) {
    $status = $_POST['status'];
    $pic = $_POST['pic_id'];
    $reason = mysqli_real_escape_string($conn, $_POST['reasoning']);

    $update = "UPDATE submissions SET status = '$status', pic_id = '$pic', admin_reasoning = '$reason' WHERE id = $id";
    if (mysqli_query($conn, $update)) {
        header("Location: dashboard_admin.php?status=updated");
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Tiket - <?php echo $data['ticket_number']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-10">
    <div class="max-w-3xl mx-auto bg-white p-10 rounded-3xl shadow-lg">
        <h2 class="text-2xl font-bold mb-6">Kelola Pengajuan: <?php echo $data['ticket_number']; ?></h2>
        
        <form action="" method="POST" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-bold text-gray-500 mb-2">Status Pengajuan</label>
                    <select name="status" class="w-full p-3 bg-gray-50 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Menunggu" <?php echo $data['status'] == 'Menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                        <option value="Proses" <?php echo $data['status'] == 'Proses' ? 'selected' : ''; ?>>Proses</option>
                        <option value="Selesai" <?php echo $data['status'] == 'Selesai' ? 'selected' : ''; ?>>Selesai</option>
                        <option value="Ditolak" <?php echo $data['status'] == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-500 mb-2">Tugaskan PIC</label>
                    <select name="pic_id" class="w-full p-3 bg-gray-50 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Teknisi --</option>
                        <?php while($t = mysqli_fetch_assoc($technicians)): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo $data['pic_id'] == $t['id'] ? 'selected' : ''; ?>>
                                <?php echo $t['full_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-500 mb-2">Reasoning / Catatan Admin</label>
                <textarea name="reasoning" rows="4" class="w-full p-3 bg-gray-50 border rounded-xl outline-none focus:ring-2 focus:ring-blue-500"><?php echo $data['admin_reasoning']; ?></textarea>
            </div>

            <div class="flex gap-4">
                <button type="submit" name="update" class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition">Simpan Perubahan</button>
                <a href="dashboard_admin.php" class="flex-1 bg-gray-100 text-center py-3 rounded-xl font-bold text-gray-600 hover:bg-gray-200 transition">Batal</a>
            </div>
        </form>
    </div>
</body>
</html>