<?php
require 'db.php';

if (isset($_POST['set_password'])) {
    $password = $_POST['password'];

    // Hash the password securely
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Check if a password already exists
    $result = $conn->query("SELECT * FROM biometric_password LIMIT 1");

    if ($result->num_rows > 0) {
        // Update existing password
        $stmt = $conn->prepare("UPDATE biometric_password SET password_hash=? WHERE id=1");
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $msg = "Password updated successfully!";
    } else {
        // Insert first password
        $stmt = $conn->prepare("INSERT INTO biometric_password (password_hash) VALUES (?)");
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $msg = "Password created successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create or Update Biometric Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <br>
    <h4>🔐 Create / Update Biometric System Password</h4>

    <?php if (isset($msg)): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="password" name="password" class="form-control mb-3" placeholder="Enter new password" required>
        <button type="submit" name="set_password" class="btn btn-primary">Save Password</button>
    </form>

</body>
</html>
