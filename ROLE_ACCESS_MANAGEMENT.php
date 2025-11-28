<?php
session_start(); // FIX: Start session before anything else
require 'db.php';

// Get all roles
$roles = $conn->query("SELECT * FROM roles ORDER BY role_name");

// Get all modules
$modules_raw = $conn->query("SELECT * FROM modules ORDER BY module_name");
$modules = [];
while ($m = $modules_raw->fetch_assoc()) {
    $modules[] = $m;
}

// Handle form submit (Save Access)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['module'])) {
    $role_id = intval($_POST['role_id']);

    // Delete old access for this role (use prepared statement)
    $stmtDel = $conn->prepare("DELETE FROM role_access WHERE role_id = ?");
    if (!$stmtDel) {
        $_SESSION['error'] = 'Database error: ' . htmlspecialchars($conn->error);
    } else {
        $stmtDel->bind_param("i", $role_id);
        if (!$stmtDel->execute()) {
            $_SESSION['error'] = 'Delete failed: ' . htmlspecialchars($conn->error);
        }
        $stmtDel->close();
    }

    // Insert new access settings
    foreach ($_POST['module'] as $module_id => $actions) {
        $module_id = intval($module_id);
        $can_view   = isset($actions['view']) ? 1 : 0;
        $can_add    = isset($actions['add']) ? 1 : 0;
        $can_edit   = isset($actions['edit']) ? 1 : 0;
        $can_delete = isset($actions['delete']) ? 1 : 0;

        // Only insert if at least one permission is granted
        if ($can_view || $can_add || $can_edit || $can_delete) {
            $stmt = $conn->prepare("
                INSERT INTO role_access (role_id, module_id, can_view, can_add, can_edit, can_delete)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                error_log('ROLE_ACCESS_MANAGEMENT: prepare failed: ' . $conn->error);
                continue;
            }
            $stmt->bind_param("iiiiii", $role_id, $module_id, $can_view, $can_add, $can_edit, $can_delete);
            if (!$stmt->execute()) {
                error_log('ROLE_ACCESS_MANAGEMENT: execute failed for module_id=' . $module_id . ': ' . $conn->error);
            }
            $stmt->close();
        }
    }

    // Set message and redirect to avoid resubmission
    $_SESSION['message'] = 'Access updated successfully!';
    header("Location: " . $_SERVER['PHP_SELF'] . "?role_id=" . $role_id);
    exit();
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
                            <option value="<?= $r['id'] ?>" <?= ((isset($_POST['role_id']) && $_POST['role_id'] == $r['id']) || (isset($_GET['role_id']) && $_GET['role_id'] == $r['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['role_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>

            <?php 
            // FIX: Allow reload to remember selected role from URL
            $current_role = isset($_GET['role_id']) ? intval($_GET['role_id'])
                    : (isset($_POST['role_id']) ? intval($_POST['role_id']) : 0);
            if (!empty($current_role)): 
            ?>
                <form method="post">
                    <input type="hidden" name="role_id" value="<?= $current_role ?>">

                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">Select</th>
                                <th>Module</th>
                                <th class="text-center">View</th>
                                <th class="text-center">Add</th>
                                <th class="text-center">Edit</th>
                                <th class="text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $access = [];
                            $stmtAccess = $conn->prepare("SELECT * FROM role_access WHERE role_id = ?");
                            if ($stmtAccess) {
                                $stmtAccess->bind_param("i", $current_role);
                                $stmtAccess->execute();
                                $res = $stmtAccess->get_result();
                                while ($a = $res->fetch_assoc()) {
                                    $access[$a['module_id']] = $a;
                                }
                                $stmtAccess->close();
                            } else {
                                error_log('ROLE_ACCESS_MANAGEMENT: prepare failed for access fetch: ' . $conn->error);
                            }

                            // Rebuild modules array from stored data
                            $modulesArray = [];
                            $stmtMod = $conn->prepare("SELECT * FROM modules ORDER BY module_name");
                            if ($stmtMod) {
                                $stmtMod->execute();
                                $resMod = $stmtMod->get_result();
                                while ($m = $resMod->fetch_assoc()) {
                                    $modulesArray[] = $m;
                                }
                                $stmtMod->close();
                            } else {
                                error_log('ROLE_ACCESS_MANAGEMENT: prepare failed for modules fetch: ' . $conn->error);
                            }

                            foreach ($modulesArray as $m):
                                $a = $access[$m['id']] ?? ['can_view'=>0,'can_add'=>0,'can_edit'=>0,'can_delete'=>0];
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="select-row">
                                    </td>
                                    <td><?= htmlspecialchars($m['module_name']) ?></td>
                                    <td class="text-center"><input type="checkbox" name="module[<?= intval($m['id']) ?>][view]" <?= (int)$a['can_view'] ? 'checked' : '' ?>></td>
                                    <td class="text-center"><input type="checkbox" name="module[<?= intval($m['id']) ?>][add]" <?= (int)$a['can_add'] ? 'checked' : '' ?>></td>
                                    <td class="text-center"><input type="checkbox" name="module[<?= intval($m['id']) ?>][edit]" <?= (int)$a['can_edit'] ? 'checked' : '' ?>></td>
                                    <td class="text-center"><input type="checkbox" name="module[<?= intval($m['id']) ?>][delete]" <?= (int)$a['can_delete'] ? 'checked' : '' ?>></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Save Access</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <!-- Success Modal -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">Success</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Access updated successfully!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
            </div>
        </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Row select logic
            document.querySelectorAll('.select-row').forEach(rowCheckbox => {
                rowCheckbox.addEventListener('change', function () {
                    const row = this.closest('tr');
                    const checkboxes = row.querySelectorAll('input[type="checkbox"]:not(.select-row)');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            });
        });
        </script>

        <?php if (!empty($_SESSION['message'])): ?>
        <script>
            var myModal = new bootstrap.Modal(document.getElementById('successModal'));
            myModal.show();
        </script>
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
    </body>
</html>