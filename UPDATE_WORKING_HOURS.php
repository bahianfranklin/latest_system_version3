<?php
    require 'db.php';

    $id = $_POST['id'];
    $time_in = $_POST['time_in'];
    $time_out = $_POST['time_out'];

    $stmt = $conn->prepare("UPDATE employee_working_hours SET time_in = ?, time_out = ? WHERE id = ?");
    $stmt->bind_param("ssi", $time_in, $time_out, $id);
    $stmt->execute();

    echo "✅ Working hours updated successfully!";
?>


