<?php
require 'db.php';
$depts = $pdo->query("SELECT DISTINCT department FROM work_details ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
header('Content-Type: application/json');
echo json_encode($depts, JSON_PRETTY_PRINT);
