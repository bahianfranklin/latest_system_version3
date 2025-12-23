<?php
require 'db.php';
$r = $conn->query("SELECT id,name FROM users WHERE status='active' LIMIT 1");
$a = $r->fetch_assoc();
if ($a) {
    echo "ID={$a['id']}; NAME={$a['name']}\n";
} else {
    echo "No active users found\n";
}
