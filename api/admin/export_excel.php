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

try {
    // Ambil Data Semua Pengajuan dari Firestore
    $submissions_query = $db->collection('submissions');
    
    // Sort by created_at DESC (requires index if filtered by where)
    // For simplicity, we fetch and sort in PHP if filtering is complex, 
    // but here we can try to apply basic date filters if they exist.
    
    $submissions_docs = $submissions_query->documents();
    
    // Fetch all users for mapping names and departments
    $users_docs = $db->collection('users')->documents();
    $users_map = [];
    foreach ($users_docs as $u_doc) {
        $users_map[$u_doc->id()] = $u_doc->data();
    }

    $filtered_data = [];
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
