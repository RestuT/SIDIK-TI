<?php
session_start();
include '../config/database.php';

// Proteksi Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak. Silakan login sebagai admin.");
}

// Konfigurasi Header Excel (MIME Type untuk .xls lama yang kompatibel dengan HTML Table)
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=Antrean_Tiket_SIDIK_TI_" . date('Y-m-d_H-i') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// --- LOGIKA FILTERING ---
$where_clauses = ["1=1"];
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, $_GET['end_date']) : '';

if (!empty($start_date) && !empty($end_date)) {
    $where_clauses[] = "DATE(s.created_at) BETWEEN '$start_date' AND '$end_date'";
} elseif (!empty($start_date)) {
    $where_clauses[] = "DATE(s.created_at) >= '$start_date'";
} elseif (!empty($end_date)) {
    $where_clauses[] = "DATE(s.created_at) <= '$end_date'";
}

$where_sql = implode(" AND ", $where_clauses);

// Ambil Data Semua Pengajuan (dengan Filter)
$query = "SELECT s.*, u.full_name as user_name, u.department, u.jabatan, pic.full_name as pic_name
          FROM submissions s 
          JOIN users u ON s.user_id = u.id 
          LEFT JOIN users pic ON s.pic_id = pic.id
          WHERE $where_sql
          ORDER BY s.created_at DESC";
$result = mysqli_query($conn, $query);

// Output Body dalam bentuk HTML Table (Excel akan otomatis mengenali ini sebagai sheet)
?>
<style>
    .text { mso-number-format:"\@"; } /* Memaksa format text untuk kolom tertentu */
</style>
<table border="1">
    <thead>
        <tr style="background-color: #4f46e5; color: #ffffff; font-weight: bold;">
            <th width="5">No</th>
            <th>No. Tiket</th>
            <th>Tanggal Pengajuan</th>
            <th>Tipe Layanan</th>
            <th>Nama Pemohon</th>
            <th>Departemen</th>
            <th>Jabatan</th>
            <th>Judul / Perangkat</th>
            <th>Urgensi</th>
            <th>Estimasi (Rp)</th>
            <th>Status</th>
            <th>PIC / Teknisi</th>
            <th>Catatan Admin</th>
            <th>Alasan Sanggah (Jika ada)</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)): 
        ?>
            <tr>
                <td align="center"><?php echo $no++; ?></td>
                <td class="text" align="left"><?php echo $row['ticket_number']; ?></td>
                <td><?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                <td><?php echo $row['type']; ?></td>
                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                <td><?php echo htmlspecialchars($row['department']); ?></td>
                <td><?php echo htmlspecialchars($row['jabatan']); ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td align="center"><?php echo $row['urgency'] ?? '-'; ?></td>
                <td align="right"><?php echo number_format($row['estimasi'], 0, ',', '.'); ?></td>
                <td align="center" style="font-weight: bold; color: <?php 
                    if($row['status'] == 'Selesai') echo '#059669'; 
                    elseif($row['status'] == 'Menunggu') echo '#d97706';
                    elseif($row['status'] == 'Ditolak') echo '#dc2626';
                    else echo '#2563eb';
                ?>;">
                    <?php echo strtoupper($row['status']); ?>
                </td>
                <td><?php echo htmlspecialchars($row['pic_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['admin_reasoning'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['appeal_reason'] ?? '-'); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
