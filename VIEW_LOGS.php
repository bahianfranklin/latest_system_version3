<?php
require 'db.php';

$result = $conn->query("SELECT a.*, u.name 
                        FROM audit_logs a 
                        LEFT JOIN users u ON a.user_id = u.id
                        ORDER BY a.id DESC");
?>

<table border="1">
    <tr>
        <th>User</th>
        <th>Action</th>
        <th>Description</th>
        <th>IP Address</th>
        <th>Device</th>
        <th>Date Time</th>
    </tr>

    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['name'] ?></td>
        <td><?= $row['action'] ?></td>
        <td><?= $row['description'] ?></td>
        <td><?= $row['ip_address'] ?></td>
        <td><?= $row['user_agent'] ?></td>
        <td><?= $row['created_at'] ?></td>
    </tr>
    <?php endwhile; ?>
</table>
