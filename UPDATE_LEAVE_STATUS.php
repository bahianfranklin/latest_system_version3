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
    die("Invalid status");
}

/* ✅ UPDATE LEAVE */
$stmt = $conn->prepare("
    UPDATE leave_requests
    SET status = ?, approver_id = ?, date_action = NOW()
    WHERE application_no = ?
");
$stmt->bind_param("sis", $new_status, $approver_id, $application_no);

if (!$stmt->execute()) {
    die("Update failed: " . $stmt->error);
}

/* ✅ AUDIT LOG (USES THE CORRECT STATUS) */
$audit_action = 'LEAVE ' . strtoupper($new_status);
$description  = "Leave application {$application_no} was {$new_status} by approver ID {$approver_id}";
logAction($conn, $approver_id, $audit_action, $description);

/* ✅ REDIRECT */
header("Location: PENDING_LEAVES.php?msg=success");
exit;
?>