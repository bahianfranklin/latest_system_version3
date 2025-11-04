<?php
require 'db.php';

function hasAccess($role_id, $module_name, $action) {
    global $conn;

    $stmt = $conn->prepare("
        SELECT ra.*
        FROM role_access ra
        INNER JOIN modules m ON m.id = ra.module_id
        WHERE ra.role_id = ? AND m.module_name = ?
        LIMIT 1
    ");
    $stmt->bind_param("is", $role_id, $module_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        switch ($action) {
            case 'view': return $row['can_view'] == 1;
            case 'add': return $row['can_add'] == 1;
            case 'edit': return $row['can_edit'] == 1;
            case 'delete': return $row['can_delete'] == 1;
        }
    }

    return false;
}
?>
