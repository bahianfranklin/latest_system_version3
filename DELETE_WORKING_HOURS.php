<?php
    require 'db.php';

    $id = $_POST['id'];

    $stmt = $conn->prepare("DELETE FROM employee_working_hours WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo "🗑️ Working hour deleted successfully!";
?>
