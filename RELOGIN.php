<?php
    session_start();

    // Remove locked session, user ID, etc.
    unset($_SESSION['locked_user_id']);
    unset($_SESSION['user_id']);
    unset($_SESSION['user']);
    session_unset();
    session_destroy();

    // Use JavaScript redirect with history replace (optional)
    // But here we'll just use a PHP redirect for reliability
    header("Location: login.php");
    exit;
?>