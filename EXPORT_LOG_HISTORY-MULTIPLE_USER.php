<?php
require 'db.php';
session_start();

// Security check
if (!isset($_SESSION['user'])) {
    exit('Unauthorized');
}

$currentUser = $_SESSION['user'];
$currentUserId = $currentUser['id'];

// Get export type
$type = $_GET['type'] ?? 'csv';

// Filters
$search       = trim($_GET['search'] ?? '');
$from         = trim($_GET['from'] ?? '');
$to           = trim($_GET['to'] ?? '');
$filter_user  = isset($_GET['filter_user']) ? (int)$_GET['filter_user'] : 0;

// Base SQL
$sql = "SELECT 
            l.id,
            u.name AS fullname,
            u.username,
            l.login_time,
            l.logout_time,
            l.ip_address
        FROM user_logs l
        JOIN users u ON l.user_id = u.id";

$conditions = [];
$params = [];
$types = "";

// 🔐 User filter
// If a specific user is selected, filter by that user. Otherwise show all users (no default to current user).
if ($filter_user > 0) {
    $conditions[] = "l.user_id = ?";
    $params[] = $filter_user;
    $types .= "i";
}

// 🔍 Search filter
if ($search !== '') {
    $conditions[] = "(u.name LIKE ? OR u.username LIKE ? OR l.ip_address LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

// 📅 Date filters
if ($from !== '') {
    $conditions[] = "l.login_time >= ?";
    $params[] = $from . " 00:00:00";
    $types .= "s";
}

if ($to !== '') {
    $conditions[] = "l.login_time <= ?";
    $params[] = $to . " 23:59:59";
    $types .= "s";
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY l.login_time DESC";

// Execute
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Convert to array
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

/* ===========================
   CSV EXPORT
=========================== */
if ($type === 'csv') {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=log_history.csv");

    $out = fopen("php://output", "w");
    fputcsv($out, ["#", "Full Name", "Username", "Login Time", "Logout Time", "IP Address"]);

    foreach ($data as $row) {
        fputcsv($out, [
            $row['id'],
            $row['fullname'],
            $row['username'],
            $row['login_time'],
            $row['logout_time'] ?? '---',
            $row['ip_address']
        ]);
    }
    fclose($out);
    exit;
}

/* ===========================
   EXCEL EXPORT
=========================== */
if ($type === 'excel') {
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=log_history.xls");

    $out = fopen("php://output", "w");
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($out, ["#", "Full Name", "Username", "Login Time", "Logout Time", "IP Address"]);

    foreach ($data as $row) {
        fputcsv($out, [
            $row['id'],
            $row['fullname'],
            $row['username'],
            $row['login_time'],
            $row['logout_time'] ?? '---',
            $row['ip_address']
        ]);
    }
    fclose($out);
    exit;
}

/* ===========================
   PDF EXPORT
=========================== */
if ($type === 'pdf') {
    require 'fpdf/fpdf.php';

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Login / Logout History', 0, 1, 'C');

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(10,8,'#',1);
    $pdf->Cell(40,8,'Full Name',1);
    $pdf->Cell(30,8,'Username',1);
    $pdf->Cell(45,8,'Login Time',1);
    $pdf->Cell(45,8,'Logout Time',1);
    $pdf->Cell(40,8,'IP Address',1);
    $pdf->Ln();

    $pdf->SetFont('Arial','',8);
    foreach ($data as $row) {
        $pdf->Cell(10,8,$row['id'],1);
        $pdf->Cell(40,8,$row['fullname'],1);
        $pdf->Cell(30,8,$row['username'],1);
        $pdf->Cell(45,8,$row['login_time'],1);
        $pdf->Cell(45,8,$row['logout_time'] ?? '---',1);
        $pdf->Cell(40,8,$row['ip_address'],1);
        $pdf->Ln();
    }

    $pdf->Output("D", "log_history.pdf");
    exit;
}

echo "Invalid export type";
