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

    // Update leave request status
    $sql = "UPDATE leave_requests 
            SET status = ?, approver_id = ?, date_action = NOW() 
            WHERE application_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $action, $approver_id, $application_no);

    if ($stmt->execute()) {
        header("Location: pending_leaves.php?msg=success");
    } else {
        echo "Error updating record: " . $conn->error;
    }
}
?>
