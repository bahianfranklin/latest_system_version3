<?php
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role_id = $_SESSION['role_id'] ?? 0;
$_SESSION['permissions'] = [];

if ($role_id > 0) {

    $sql = "
        SELECT 
            m.module_name,
            ra.can_view,
            ra.can_add,
            ra.can_edit,
            ra.can_delete
        FROM role_access ra
        INNER JOIN modules m ON m.id = ra.module_id
        WHERE ra.role_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $_SESSION['permissions'][$row['module_name']] = [
            'view'   => (bool)$row['can_view'],
            'add'    => (bool)$row['can_add'],
            'edit'   => (bool)$row['can_edit'],
            'delete' => (bool)$row['can_delete']
        ];
    }
}
