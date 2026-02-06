<?php
session_start();

require 'db.php';
require 'audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

if (!isset($_SESSION['user_id'], $_POST['application_no'], $_POST['action'])) {
    die("Invalid data");
}

$approver_id = (int) $_SESSION['user_id'];
$application_no = trim($_POST['application_no']);
$status = trim($_POST['action']);

if (!in_array($status,['Approved','Rejected'])) {
    die("Invalid status");
}

/* ===============================
   UPDATE FAILURE CLOCK
================================ */

$sql = "
UPDATE failure_clock
SET status = ?,
    approver_id = ?,
    datetime_action = NOW()
WHERE application_no = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sis",$status,$approver_id,$application_no);

if (!$stmt->execute()) {
    die("Update failed");
}

/* ===============================
   AUDIT
================================ */

$audit_action = "FAILURE CLOCK ".strtoupper($status);
$description = "Application $application_no $status by approver ID $approver_id";

logAction($conn,$approver_id,$audit_action,$description);

/* ===============================
   RETURN
================================ */

header("Location: ".$_SERVER['HTTP_REFERER']."?success=1");
exit;
