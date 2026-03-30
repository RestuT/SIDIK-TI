<?php
$conn = mysqli_connect("localhost", "root", "", "sidik_ti");
if (!$conn) die("FAIL");
$res = mysqli_query($conn, "SHOW TABLES");
while($row = mysqli_fetch_array($res)) {
    echo $row[0] . "\n";
}
?>
