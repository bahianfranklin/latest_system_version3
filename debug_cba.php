<?php
require 'db.php';

echo "<h2>DEBUGGING College of Business and Accountancy</h2>";

// Check departments
echo "<h3>All Departments in DB:</h3>";
$result = $conn->query("SELECT DISTINCT department FROM departments ORDER BY department");
while($row = $result->fetch_assoc()) {
    echo $row['department'] . "<br>";
}

// Check employees in College of Business and Accountancy
echo "<h3>Employees in 'College of Business and Accountancy':</h3>";
$result = $conn->query("SELECT u.id, u.name, u.status, wd.department FROM users u JOIN work_details wd ON wd.user_id = u.id WHERE wd.department = 'College of Business and Accountancy'");
echo "Found: " . $result->num_rows . " employees<br>";
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Name: " . $row['name'] . " | Status: " . $row['status'] . "<br>";
}

// Check if there are any attendance logs for these employees
echo "<h3>Attendance Logs for CBA Employees:</h3>";
$result = $conn->query("SELECT al.user_id, u.name, COUNT(*) as log_count FROM attendance_logs al JOIN users u ON u.id = al.user_id JOIN work_details wd ON wd.user_id = u.id WHERE wd.department = 'College of Business and Accountancy' GROUP BY u.id");
echo "Found: " . $result->num_rows . " employees with logs<br>";
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['user_id'] . " | Name: " . $row['name'] . " | Logs: " . $row['log_count'] . "<br>";
}

// Check if there's a "College of Business and Accountancy" in departments table
echo "<h3>Check departments table matching:</h3>";
$result = $conn->query("SELECT * FROM departments WHERE department LIKE '%Business%' OR department LIKE '%Accountancy%' OR department LIKE '%CBA%'");
echo "Found: " . $result->num_rows . " matching departments<br>";
while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Dept: " . $row['department'] . "<br>";
}
?>
