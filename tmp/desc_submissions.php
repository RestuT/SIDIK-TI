<?php
$conn = mysqli_connect("localhost", "root", "", "sidik_ti");
$res = mysqli_query($conn, "DESC submissions");
echo "SUBMISSIONS TABLE:\n";
while($row = mysqli_fetch_array($res)) {
    echo "- " . $row[0] . " (" . $row[1] . ")\n";
}
?>
