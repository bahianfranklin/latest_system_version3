<?php
require 'db.php';

// Get all roles
$roles = $conn->query("SELECT * FROM roles ORDER BY role_name");

// Get all modules
$modules = $conn->query("SELECT * FROM modules ORDER BY module_name");

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role_id = $_POST['role_id'];

    // Delete old access for this role
    $conn->query("DELETE FROM role_access WHERE role_id = $role_id");

    // Insert new access settings
    foreach ($_POST['module'] as $module_id => $actions) {
        $can_view   = isset($actions['view']) ? 1 : 0;
        $can_add    = isset($actions['add']) ? 1 : 0;
        $can_edit   = isset($actions['edit']) ? 1 : 0;
        $can_delete = isset($actions['delete']) ? 1 : 0;

        $stmt = $conn->prepare("
            INSERT INTO role_access (role_id, module_id, can_view, can_add, can_edit, can_delete)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiiiii", $role_id, $module_id, $can_view, $can_add, $can_edit, $can_delete);
        $stmt->execute();
    }

    echo "<div class='alert alert-success'>Access updated successfully!</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Role Access Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container bg-white p-4 rounded shadow-sm">
    <h3 class="mb-4">Role Access Management</h3>

    <!-- Select Role -->
    <form method="post">
        <div class="mb-3">
            <label for="role_id" class="form-label">Select Role</label>
            <select name="role_id" id="role_id" class="form-select" required onchange="this.form.submit()">
                <option value="">-- Choose Role --</option>
                <?php while ($r = $roles->fetch_assoc()): ?>
                    <option value="<?= $r['id'] ?>" <?= (isset($_POST['role_id']) && $_POST['role_id'] == $r['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['role_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </form>

    <?php if (!empty($_POST['role_id'])): ?>
        <form method="post">
            <input type="hidden" name="role_id" value="<?= $_POST['role_id'] ?>">

            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Module</th>
                        <th class="text-center">View</th>
                        <th class="text-center">Add</th>
                        <th class="text-center">Edit</th>
                        <th class="text-center">Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $role_id = $_POST['role_id'];
                    $access = [];
                    $res = $conn->query("SELECT * FROM role_access WHERE role_id = $role_id");
                    while ($a = $res->fetch_assoc()) {
                        $access[$a['module_id']] = $a;
                    }

                    $modules->data_seek(0); // Reset pointer
                    while ($m = $modules->fetch_assoc()):
                        $a = $access[$m['id']] ?? ['can_view'=>0,'can_add'=>0,'can_edit'=>0,'can_delete'=>0];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($m['module_name']) ?></td>
                            <td class="text-center"><input type="checkbox" name="module[<?= $m['id'] ?>][view]" <?= $a['can_view'] ? 'checked' : '' ?>></td>
                            <td class="text-center"><input type="checkbox" name="module[<?= $m['id'] ?>][add]" <?= $a['can_add'] ? 'checked' : '' ?>></td>
                            <td class="text-center"><input type="checkbox" name="module[<?= $m['id'] ?>][edit]" <?= $a['can_edit'] ? 'checked' : '' ?>></td>
                            <td class="text-center"><input type="checkbox" name="module[<?= $m['id'] ?>][delete]" <?= $a['can_delete'] ? 'checked' : '' ?>></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Save Access</button>
            </div>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
