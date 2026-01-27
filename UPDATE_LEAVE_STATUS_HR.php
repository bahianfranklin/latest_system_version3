<?php
session_start();
require 'db.php';
require 'audit.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

$approver_id = (int) $_SESSION['user_id'];
$application_no = $_POST['application_no'] ?? null;
$action = $_POST['action'] ?? null;

if (!$application_no || !in_array($action, ['Approved', 'Rejected'])) {
    die("Invalid request");
}

/* =========================================
   VERIFY HR AUTHORITY
========================================= */
$sqlVerify = "
    SELECT lr.id
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    JOIN work_details wd ON u.id = wd.user_id
    JOIN departments d ON d.department = wd.department
    JOIN hr_approver_assignments haa ON haa.department_id = d.id
    WHERE lr.application_no = ?
      AND haa.user_id = ?
      AND lr.status = 'Pending'
";

$stmt = $conn->prepare($sqlVerify);
if (!$stmt) die("SQL Error in Verify: " . $conn->error);
$stmt->bind_param("si", $application_no, $approver_id);
$stmt->execute();

if ($stmt->get_result()->num_rows === 0) {
    die("You are not allowed to approve this leave.");
}

/* =========================================
   UPDATE STATUS
========================================= */
$sqlUpdate = "
    UPDATE leave_requests
    SET status = ?,
        approver_id = ?,
        date_action = NOW()
    WHERE application_no = ?
";

$stmt2 = $conn->prepare($sqlUpdate);
if (!$stmt2) die("SQL Error in Update: " . $conn->error);
$stmt2->bind_param("sis", $action, $approver_id, $application_no);
$stmt2->execute();

/* =========================================
   AUDIT LOG
========================================= */
logAction(
    $conn,
    $approver_id,
    "Leave {$action}",
    "Application No: {$application_no}"
);

header("Location: HR_LEAVE_APPROVAL.php");
exit;
