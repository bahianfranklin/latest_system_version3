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

/* ✅ CHECK IF APPROVER IS AUTHORIZED */
$stmtCheck = $conn->prepare("
    SELECT 1
    FROM change_schedule cs
    JOIN users u ON cs.applied_by = u.id
    JOIN work_details wd ON u.id = wd.user_id
    JOIN departments d ON wd.department = d.id
    JOIN hr_approver_assignments aa 
        ON (aa.department_id = d.id OR aa.work_detail_id = wd.id)
    WHERE cs.application_no = ? AND aa.user_id = ?
");
$stmtCheck->bind_param("si", $application_no, $approver_id);
$stmtCheck->execute();
$stmtCheck->store_result();

if ($stmtCheck->num_rows === 0) {
    die("You are not authorized to approve/reject this application");
}

/* ✅ UPDATE CHANGE SCHEDULE */
$stmt = $conn->prepare("
    UPDATE change_schedule
    SET status = ?, datetime_action = NOW(), approved_by = ?
    WHERE application_no = ?
");
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->bind_param("sis", $new_status, $approver_id, $application_no);

if (!$stmt->execute()) {
    die("Update failed: " . $stmt->error);
}

/* ✅ AUDIT LOG */
$audit_action = 'CHANGE SCHEDULE ' . strtoupper($new_status);
$description  = "Change Schedule application {$application_no} was {$new_status} by approver ID {$approver_id}";
logAction($conn, $approver_id, $audit_action, $description);

/* ✅ REDIRECT */
header("Location: approver_change_schedule.php?msg=success");
exit;
?>
