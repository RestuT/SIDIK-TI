<?php
// SIDIK-TI Database Repair & Setup Script
include '../config/database.php';

echo "<h1>SIDIK-TI Database Setup</h1>";

// 1. Create asset_assignments table
$sql = "CREATE TABLE IF NOT EXISTS asset_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    serial_number VARCHAR(100) UNIQUE,
    category VARCHAR(50) NOT NULL,
    assigned_at DATE NOT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    INDEX (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color:green;'>✅ Table 'asset_assignments' is ready.</p>";
    
    // 2. Add dummy data if empty
    $check = mysqli_query($conn, "SELECT COUNT(*) as count FROM asset_assignments");
    $row = mysqli_fetch_assoc($check);
    if($row['count'] == 0) {
        // Try to find a user to assign to
        $user_res = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
        if($u = mysqli_fetch_assoc($user_res)) {
            $uid = $u['id'];
            $ins = "INSERT INTO asset_assignments (user_id, item_name, serial_number, category, assigned_at, status) VALUES 
                    ($uid, 'MacBook Pro M2 14-inch', 'SN-MBP-2024-001', 'Laptop', CURDATE(), 'Active'),
                    ($uid, 'Dell UltraSharp 27\"', 'SN-MON-2024-005', 'Monitor', CURDATE(), 'Active'),
                    ($uid, 'Logitech MX Master 3S', 'SN-MOU-2024-012', 'Peripheral', CURDATE(), 'Active')";
            if (mysqli_query($conn, $ins)) {
                echo "<p style='color:green;'>✅ Dummy asset data added successfully.</p>";
            }
        } else {
            echo "<p style='color:orange;'>⚠️ No users found in database. Please register a user first to see assets.</p>";
        }
    }
} else {
    echo "<p style='color:red;'>❌ Error creating table: " . mysqli_error($conn) . "</p>";
}

echo "<hr><a href='../modules_user/dashboard_user.php'>Go to Dashboard</a>";
?>
