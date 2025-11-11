<?php
require 'db.php';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
        //      ✅ Step 1: Get user details before deleting
        //     $stmt = $conn->prepare("SELECT name, email, username FROM users WHERE id = ?");
        //     $stmt->bind_param("i", $id);
        //     $stmt->execute();
        //     $result = $stmt->get_result();
        //     if ($row = $result->fetch_assoc()) {
        //         $userDetails = "Name: {$row['name']} | Email: {$row['email']} | Username: {$row['username']}";
        //     }
        //     $stmt->close();


        // ✅ Delete logs first
        $stmt = $conn->prepare("DELETE FROM user_logs WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // ✅ Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    // ✅ Instead of redirect loop, just go back
    echo "<script>
        alert('User deleted successfully!');
        window.location.href = 'users.php';
    </script>";
    exit;
?>


