<?php
session_start();
require 'db.php';

$approver_id = $_SESSION['user_id'] ?? null;
if (!$approver_id) {
    die("Not logged in");
}

if (!isset($_POST['application_no'], $_POST['action'])) {
    die("Invalid request");
}

$application_no = $_POST['application_no'];
$action = $_POST['action'];

// Validate action
$validActions = ['Approved', 'Rejected'];
if (!in_array($action, $validActions)) {
    die("Invalid action");
}

// Update the overtime request with approver info
$stmt = $conn->prepare("
    UPDATE overtime
    SET status = ?, 
        approver_id = ?,
        datetime_action = NOW()
    WHERE application_no = ?
      AND status = 'Pending'
");


if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("sis", $action, $approver_id, $application_no);

if ($stmt->execute()) {
    // Optional: audit log
    /*
    $stmtLog = $conn->prepare("
        INSERT INTO audit_logs (user_id, action, reference_no, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmtLog->bind_param("iss", $approver_id, $action, $application_no);
    $stmtLog->execute();
    */
    header("Location: HR_OVERTIME_APPROVAL.php?status=success");
    exit;
} else {
    die("Failed to update overtime request: " . $conn->error);
}
?>
