<?php
$host = "localhost";          // database host
$user = "root";               // database username
$pass = "";                   // database password
$dbname = "login_system";     // database name

// ✅ MySQLi connection
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed (MySQLi): " . $conn->connect_error);
}

// ✅ PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed (PDO): " . $e->getMessage());
}
