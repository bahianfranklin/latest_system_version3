<?php
session_start();

require 'db.php';
require 'audit.php';

/* ===============================
   REQUEST METHOD CHECK
================================ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

/* ===============================
   REQUIRED FIELDS CHECK
================================ */

if (empty($_SESSION['user_id']) || empty($_POST['application_no']) || empty($_POST['action'])) {
    die("Missing required fields");
}

/* ===============================
   VARIABLES
================================ */

$approver_id    = (int) $_SESSION['user_id'];
$application_no = trim($_POST['application_no']);
$new_status     = trim($_POST['action']); // Approved | Rejected

/* ===============================
   VALIDATE STATUS
================================ */

if (!in_array($new_status, ['Approved', 'Rejected'], true)) {
    die("Invalid status value");
}

/* ===============================
   UPDATE WORK RESTDAY
================================ */

$sql = "
    UPDATE work_restday
    SET 
        status = ?,
        approver_id = ?,
        datetime_action = NOW()
    WHERE application_no = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param("sis", $new_status, $approver_id, $application_no);

if (!$stmt->execute()) {
    die("Update failed: " . $stmt->error);
}

/* ===============================
   AUDIT LOG
================================ */

$audit_action = 'WORK RESTDAY ' . strtoupper($new_status);
$description  = "Work Restday {$application_no} was {$new_status} by HR approver ID {$approver_id}";

logAction($conn, $approver_id, $audit_action, $description);

/* ===============================
   REDIRECT
================================ */

header("Location: HR_WORK_RESTDAY_APPROVAL.php?msg=success");
exit;
