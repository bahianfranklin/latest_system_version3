<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $_SESSION['locked_user_id'] = $_SESSION['user_id'];
    unset($_SESSION['user_id']); // Optional: prevent access until unlock
    header("Location: lockscreen.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
