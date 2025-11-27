<?php
session_start();

require 'db.php';
require 'audit.php';

/* ✅ POST + AUTH CHECK */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

if (!isset($_SESSION['user_id'], $_POST['application_no'], $_POST['action'])) {
    die("Invalid request");
}

$approver_id    = (int) $_SESSION['user_id'];
$application_no = trim($_POST['application_no']);
$new_status     = trim($_POST['action']); // Approved | Rejected

/* ✅ VALIDATE STATUS */
if (!in_array($new_status, ['Approved', 'Rejected'], true)) {
    die("Invalid status value");
}

/* ✅ UPDATE WORK RESTDAY */
$stmt = $conn->prepare("
    UPDATE work_restday
    SET status = ?, datetime_action = NOW()
    WHERE application_no = ?
");

if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("ss", $new_status, $application_no);

if (!$stmt->execute()) {
    die("Update failed: " . $stmt->error);
}

/* ✅ AUDIT LOG */
$audit_action = 'WORK RESTDAY ' . strtoupper($new_status);
$description  = "Work Restday application {$application_no} was {$new_status} by approver ID {$approver_id}";

logAction($conn, $approver_id, $audit_action, $description);

/* ✅ REDIRECT */
header("Location: approver_work_restday.php?msg=success");
exit;
