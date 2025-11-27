<?php
require 'db.php';
require 'audit.php';

$id = $_POST['id'];

// Fetch old data BEFORE DELETE
$old = $conn->query("SELECT user_id, work_day, time_in, time_out 
                     FROM employee_working_hours WHERE id=$id")->fetch_assoc();

$stmt = $conn->prepare("DELETE FROM employee_working_hours WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {

    // 🔹 AUDIT LOG
    $desc = "Deleted working hours for {$old['work_day']} ({$old['time_in']} - {$old['time_out']})";
    logAction($old['user_id'], "DELETE", $desc);

    echo "Deleted successfully!";
} else {
    echo "Error: " . $stmt->error;
}
?>
