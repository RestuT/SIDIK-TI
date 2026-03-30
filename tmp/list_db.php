<?php
$conn = mysqli_connect("localhost", "root", "");
$res = mysqli_query($conn, "SHOW DATABASES");
while($row = mysqli_fetch_array($res)) {
    echo $row[0] . "\n";
}
?>
