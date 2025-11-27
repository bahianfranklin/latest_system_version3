<?php
require 'db.php';
require 'audit.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1); // ✅ FIXED

if (
    isset($_POST['user_id'], $_POST['work_day'], $_POST['time_in'], $_POST['time_out'])
) {
    $user_id  = $_POST['user_id'];
    $work_day = $_POST['work_day'];
    $time_in  = $_POST['time_in'];
    $time_out = $_POST['time_out'];

    $stmt = $conn->prepare("
        INSERT INTO employee_working_hours (user_id, work_day, time_in, time_out)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $user_id, $work_day, $time_in, $time_out);

    if ($stmt->execute()) {

        // ✅ AUDIT
        if (!isset($_SESSION['user_id'])) {
            die("AUDIT ERROR: session user_id not set");
        }

        $description = "Added working hours: $work_day ($time_in - $time_out)";
        logAction($conn, $_SESSION['user_id'], "ADD", $description);

        echo "Working hours added successfully.";
    } else {
        echo "Insert error: " . $stmt->error;
    }
} else {
    echo "Incomplete data.";
}
?>
