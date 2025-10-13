<?php
session_start();
require 'db.php';

$approver_id = $_SESSION['user_id'] ?? null;
if (!$approver_id) {
    die("Not logged in");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_no = $_POST['application_no'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($application_no && in_array($action, ['Approved', 'Rejected'])) {
        // ✅ Update status + datetime_action
        $sql = "UPDATE failure_clock 
                SET status = ?, datetime_action = NOW() 
                WHERE application_no = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("ss", $action, $application_no);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Failure Clock request updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating Failure Clock request: " . $conn->error;
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid request.";
    }
}

// 🔙 Redirect back
header("Location: approver_failure_clock.php");
exit();
