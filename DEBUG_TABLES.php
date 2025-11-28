<?php
// Quick diagnostic to check table and column existence
require_once 'db.php';

$tables = ['overtime', 'official_business', 'change_schedule', 'failure_clock', 'clock_alteration', 'work_restday'];

echo "<h2>Database Table & Column Check</h2>";

foreach ($tables as $table) {
    echo "<h3>Table: <code>{$table}</code></h3>";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '{$table}'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Table exists</p>";
        
        // Show columns
        $descResult = $conn->query("DESCRIBE {$table}");
        if ($descResult) {
            echo "<table border='1' style='margin: 10px 0;'><tr><th>Field</th><th>Type</th></tr>";
            while ($col = $descResult->fetch_assoc()) {
                echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>✗ Table NOT FOUND</p>";
    }
    echo "<hr>";
}
?>
