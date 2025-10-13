<?php

    session_start();
    require 'db.php'; // this defines $conn, not $pdo

    if (isset($_SESSION['log_id'])) {
        $log_id = $_SESSION['log_id'];

        // ✅ Use MySQLi prepared statement
        $stmt = $conn->prepare("UPDATE user_logs SET logout_time = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $log_id);
            $stmt->execute();
        }
    }

    /* Handle submit BEFORE any HTML */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
            // Kill all session data
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();

            header('Location: login.php');
            exit;
        }

        if (isset($_POST['confirm']) && $_POST['confirm'] === 'no') {
            header('Location: index.php');
            exit;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logout</title>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

</head>
<body class="d-flex flex-column min-vh-100 bg-primary">
    <!-- Main Content -->
    <main class="flex-grow-1 d-flex justify-content-center align-items-center">
        <div class="card shadow p-4 text-center" style="max-width: 400px; width: 100%;">
            <h3 class="mb-3">Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <form method="post">
                <button type="submit" name="confirm" value="yes" class="btn btn-danger me-2">Yes, Logout</button>
                <button type="submit" name="confirm" value="no" class="btn btn-secondary">No, Stay</button>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/layout/FOOTER'; ?>
    
</body>
</html>
