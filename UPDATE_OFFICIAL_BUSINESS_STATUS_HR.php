<?php
session_start();
require 'db.php';
require 'audit.php'; // optional

/* ===============================
   🔐 BASIC CHECKS
================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method");
}

if (!isset($_SESSION['user_id'], $_POST['application_no'], $_POST['action'])) {
    die("Invalid request");
}

$approver_id    = (int) $_SESSION['user_id'];
$application_no = $_POST['application_no'];
$action         = $_POST['action'];

if (!in_array($action, ['Approved', 'Rejected'])) {
    die("Invalid action");
}

/* ===============================
   🔎 VERIFY HR AUTHORITY (FIXED)
================================ */
$checkHR = "
    SELECT 1
    FROM official_business ob
    INNER JOIN users u 
        ON ob.applied_by = u.id
    INNER JOIN work_details wd 
        ON wd.user_id = u.id
    INNER JOIN departments d 
        ON d.department = wd.department
    INNER JOIN hr_approver_assignments haa
        ON haa.department_id = d.id
    WHERE haa.user_id = ?
      AND ob.application_no = ?
";

$stmtCheck = $conn->prepare($checkHR);
if (!$stmtCheck) {
    die("SQL ERROR (CHECK HR): " . $conn->error);
}

$stmtCheck->bind_param("is", $approver_id, $application_no);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();

if ($resultCheck->num_rows === 0) {
    die("Unauthorized HR approval");
}

/* ===============================
   ✅ UPDATE OFFICIAL BUSINESS
================================ */
if ($action === 'Approved') {
    $update = "
        UPDATE official_business
        SET status = ?, approved_by = ?, datetime_action = NOW()
        WHERE application_no = ? AND status = 'Pending'
    ";
    $stmtUpdate->bind_param("sis", $action, $approver_id, $application_no);
} else {
    $update = "
        UPDATE official_business
        SET status = ?, rejected_by = ?, datetime_action = NOW()
        WHERE application_no = ? AND status = 'Pending'
    ";
    $stmtUpdate->bind_param("sis", $action, $approver_id, $application_no);
}

/* ===============================
   🧾 AUDIT LOG (OPTIONAL)
================================ */
if (function_exists('audit_log')) {
    audit_log(
        $approver_id,
        "OFFICIAL BUSINESS {$action}",
        "Application No: {$application_no}"
    );
}

/* ===============================
   🔁 REDIRECT BACK
================================ */
header("Location: HR_OFFICIAL_BUSINESS_APPROVAL.php");
exit;
