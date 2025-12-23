<?php
require 'db.php';
$id = 8;
$stmt = $pdo->prepare('SELECT u.*, w.employee_no FROM users u LEFT JOIN work_details w ON w.user_id = u.id WHERE u.id = ?');
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
header('Content-Type: application/json');
echo json_encode($r ?: null);
