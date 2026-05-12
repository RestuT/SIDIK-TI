<?php
require_once __DIR__ . '/api/config/database.php';
if ($conn) {
    $res = mysqli_query($conn, "SELECT id, username, role, full_name FROM users");
    while ($row = mysqli_fetch_assoc($res)) {
        print_r($row);
    }
} else if ($db) {
    $users = $db->collection('users')->documents();
    foreach ($users as $u) {
        if ($u->exists()) {
            print_r($u->data());
        }
    }
}
?>
