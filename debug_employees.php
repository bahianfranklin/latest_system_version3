<?php
require 'db.php';
$emps = $pdo->query("SELECT u.id, u.name, w.employee_no, w.department, w.position FROM users u LEFT JOIN work_details w ON w.user_id = u.id WHERE u.status='active' ORDER BY u.name")->fetchAll();
header('Content-Type: application/json');
echo json_encode($emps, JSON_PRETTY_PRINT);
