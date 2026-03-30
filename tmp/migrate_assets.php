<?php
$conn = mysqli_connect("localhost", "root", "", "sidik_ti");
if (!$conn) die("CONNECTION FAIL: " . mysqli_connect_error());

$sql = "CREATE TABLE IF NOT EXISTS asset_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    serial_number VARCHAR(100) UNIQUE,
    category VARCHAR(50) NOT NULL,
    assigned_at DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "TABLE asset_assignments CREATED SUCCESS\n";
    
    // Add some dummy data for test if empty
    $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM asset_assignments");
    $row = mysqli_fetch_assoc($check);
    if($row['count'] == 0) {
        $user_res = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
        if($u = mysqli_fetch_assoc($user_res)) {
            $uid = $u['id'];
            $ins = "INSERT INTO asset_assignments (user_id, item_name, serial_number, category, assigned_at, status) VALUES 
                    ($uid, 'MacBook Pro M2 14-inch', 'SN-MBP-2024-001', 'Laptop', CURDATE(), 'Active'),
                    ($uid, 'Dell UltraSharp 27\"', 'SN-MON-2024-005', 'Monitor', CURDATE(), 'Active'),
                    ($uid, 'Logitech MX Master 3S', 'SN-MOU-2024-012', 'Peripheral', CURDATE(), 'Active')";
            if (mysqli_query($conn, $ins)) {
                echo "DUMMY DATA ADDED SUCCESS\n";
            }
        }
    }
} else {
    echo "ERROR CREATING TABLE: " . mysqli_error($conn);
}
?>
