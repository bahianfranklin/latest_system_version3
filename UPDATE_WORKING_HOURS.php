<?php
require 'db.php';
require 'audit.php';

$id = $_POST['id'];
$time_in = $_POST['time_in'];
$time_out = $_POST['time_out'];

// Get user_id + day BEFORE update
$old = $conn->query("SELECT user_id, work_day, time_in AS old_in, time_out AS old_out 
                     FROM employee_working_hours WHERE id=$id")->fetch_assoc();

$stmt = $conn->prepare("
    UPDATE employee_working_hours
    SET time_in=?, time_out=?
    WHERE id=?
");
$stmt->bind_param("ssi", $time_in, $time_out, $id);

if ($stmt->execute()) {

    // 🔹 AUDIT LOG
    $desc = "Updated working hours for {$old['work_day']} FROM ({$old['old_in']} - {$old['old_out']}) TO ($time_in - $time_out)";
    logAction($old['user_id'], "UPDATE", $desc);

    echo "Updated successfully!";
} else {
    echo "Error: " . $stmt->error;
}
?>