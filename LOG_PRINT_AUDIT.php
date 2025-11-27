<?php
require 'db.php';
require 'autolock.php';

session_start();
$user_id = $_SESSION['user_id'] ?? 0;

if (file_exists(__DIR__ . '/AUDIT.php')) {
    require_once __DIR__ . '/AUDIT.php';
}

if ($user_id > 0) {
    logAction($conn, $user_id, "PRINT PROFILE", "User printed all profile data");
}

echo json_encode(["status" => "logged"]);
?>
