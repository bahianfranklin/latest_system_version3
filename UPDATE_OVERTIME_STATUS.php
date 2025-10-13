<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_no = $_POST['application_no'];
    $action = $_POST['action'];
    $approver_id = $_SESSION['user_id'] ?? null;

    if (!$approver_id) {
        die("Not logged in!");
    }

    // ✅ Update overtime request status
    $sql = "UPDATE overtime 
            SET status = ?, datetime_action = NOW() 
            WHERE application_no = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Error: " . $conn->error);
    }

    // "ss" = both are strings (status + application_no)
    $stmt->bind_param("ss", $action, $application_no);

    if ($stmt->execute()) {
        header("Location: approver_overtime.php?msg=success");
        exit;
    } else {
        echo "Error updating record: " . $stmt->error;
    }
}
?>
