<?php
include 'config/database.php';
$res = mysqli_query($conn, "SHOW TABLES");
echo "TABLES IN DATABASE:\n";
while($row = mysqli_fetch_array($res)) {
    echo "- " . $row[0] . "\n";
    $desc = mysqli_query($conn, "DESCRIBE " . $row[0]);
    while($d = mysqli_fetch_assoc($desc)) {
        echo "  - " . $d['Field'] . " (" . $d['Type'] . ")\n";
    }
}
?>
