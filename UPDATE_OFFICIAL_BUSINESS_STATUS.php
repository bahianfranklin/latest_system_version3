<?php
session_start();
require 'db.php';

// Get approver ID (optional, only if you add approver_id column later)
$approver_id = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_no = $_POST['application_no'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($application_no && in_array($action, ['Approved', 'Rejected'])) {
        // ✅ Update query (NO approver_id since your table doesn’t have it yet)
        $sql = "UPDATE official_business 
                SET status = ?, datetime_action = NOW() 
                WHERE application_no = ?";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL Error: " . $conn->error);
        }

        $stmt->bind_param("ss", $action, $application_no);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Official Business updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating Official Business.";
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid request.";
    }
}

// Redirect back to approver page
header("Location: approver_official_business.php");
exit();
