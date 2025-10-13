<?php
session_start();
require 'db.php';

// Optional approver tracking if you add approver_id later
$approver_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_no = $_POST['application_no'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($application_no && in_array($action, ['Approved', 'Rejected'])) {
        // ✅ Update status + datetime only
        $sql = "UPDATE change_schedule 
                SET status = ?, datetime_action = NOW() 
                WHERE application_no = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("ss", $action, $application_no);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Change Schedule updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating Change Schedule.";
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid request.";
    }
}

// Redirect back to Approver page
header("Location: approver_change_schedule.php");
exit();
