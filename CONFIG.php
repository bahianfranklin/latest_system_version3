<?php
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // API Base URL
    $baseUrl = "https://api.mandbox.com/apitest/v1/contact.php";

    // ✅ MySQL Database Connection
    $servername = "localhost";
    $username = "root"; // Change this if needed
    $password = "";     // Change this if needed
    $database = "login_system"; // ✅ Replace this with your actual DB name

    $conn = new mysqli($servername, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Universal cURL request function
    function requestData($url, $method = "GET", $data = []) {
        $ch = curl_init();

        $method = strtoupper($method);

        if ($method === "GET" && !empty($data)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === "POST") {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return json_encode(["error" => $error]);
        }

        curl_close($ch);
        return $response;
    }
?>

