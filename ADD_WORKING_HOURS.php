<?php
    require 'db.php';

    if (!empty($_POST['user_id']) && !empty($_POST['work_day']) && !empty($_POST['time_in']) && !empty($_POST['time_out'])) {
        $stmt = $conn->prepare("INSERT INTO employee_working_hours (user_id, work_day, time_in, time_out) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $_POST['user_id'], $_POST['work_day'], $_POST['time_in'], $_POST['time_out']);

        if ($stmt->execute()) {
            echo "Working hours added successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Incomplete data.";
    }
?>
