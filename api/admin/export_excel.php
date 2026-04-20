<?php
ob_start();

require_once __DIR__ . '/../config/database.php';

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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$filtered_data = [];

try {
    if ($db) {
        // --- FIRESTORE LOGIC ---
        $submissions_query = $db->collection('submissions');
        $submissions_docs = $submissions_query->documents();
        
        // Fetch all users for mapping names and departments
        $users_docs = $db->collection('users')->documents();
        $users_map = [];
        foreach ($users_docs as $u_doc) {
            $users_map[$u_doc->id()] = $u_doc->data();
        }

        foreach ($submissions_docs as $doc) {
            $row = $doc->data();
            $row['id'] = $doc->id();
            
            $created_at = $row['created_at'] ?? '';
            $date_only = substr($created_at, 0, 10);
            
            // Manual Filter by Date
            if (!empty($start_date) && $date_only < $start_date) continue;
            if (!empty($end_date) && $date_only > $end_date) continue;
            
            // Map User Data
            $user_id = $row['user_id'] ?? '';
            $pic_id = $row['pic_id'] ?? '';
            
            $row['user_name'] = $users_map[$user_id]['full_name'] ?? 'Unknown';
            $row['department'] = $users_map[$user_id]['department'] ?? 'Unknown';
            $row['jabatan'] = $users_map[$user_id]['jabatan'] ?? 'Unknown';
            $row['pic_name'] = $users_map[$pic_id]['full_name'] ?? '-';
            
            $filtered_data[] = $row;
        }

        // Sort by created_at DESC in PHP
        usort($filtered_data, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

    } else if ($conn) {
        // --- MYSQL LOGIC ---
        $where_clauses = ["1=1"];
        if (!empty($start_date)) {
            $where_clauses[] = "DATE(s.created_at) >= '" . mysqli_real_escape_string($conn, $start_date) . "'";
        }
        if (!empty($end_date)) {
            $where_clauses[] = "DATE(s.created_at) <= '" . mysqli_real_escape_string($conn, $end_date) . "'";
        }
        $where_sql = implode(" AND ", $where_clauses);

        $query = "SELECT 
                    s.*, 
                    u.full_name as user_name, 
                    u.department as department, 
                    u.jabatan as jabatan,
                    p.full_name as pic_name
                  FROM submissions s
                  LEFT JOIN users u ON s.user_id = u.id
                  LEFT JOIN users p ON s.pic_id = p.id
                  WHERE $where_sql
                  ORDER BY s.created_at DESC";
        
        $res = mysqli_query($conn, $query);
        if (!$res) {
            throw new Exception("MySQL Query Error: " . mysqli_error($conn));
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $filtered_data[] = $row;
        }
    } else {
        throw new Exception("Tidak ada koneksi database yang tersedia.");
    }

} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}

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
        foreach ($filtered_data as $row): 
        ?>
            <tr>
                <td align="center"><?php echo $no++; ?></td>
                <td class="text" align="left"><?php echo htmlspecialchars($row['ticket_number'] ?? ''); ?></td>
                <td><?php echo !empty($row['created_at']) ? date('d-m-Y H:i', strtotime($row['created_at'])) : '-'; ?></td>
                <td><?php echo htmlspecialchars($row['type'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['user_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['department'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['jabatan'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['title'] ?? ''); ?></td>
                <td align="center"><?php echo htmlspecialchars($row['urgency'] ?? '-'); ?></td>
                <td align="right"><?php echo number_format((float)($row['estimasi'] ?? 0), 0, ',', '.'); ?></td>
                <td align="center" style="font-weight: bold; color: <?php 
                    $status = $row['status'] ?? '';
                    if($status == 'Selesai') echo '#059669'; 
                    elseif($status == 'Menunggu') echo '#d97706';
                    elseif($status == 'Ditolak') echo '#dc2626';
                    else echo '#2563eb';
                ?>;">
                    <?php echo strtoupper($status); ?>
                </td>
                <td><?php echo htmlspecialchars($row['pic_name'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['admin_reasoning'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['appeal_reason'] ?? '-'); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
