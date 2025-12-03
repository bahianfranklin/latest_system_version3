<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// ADD new module
if (isset($_POST['add'])) {
    $name = trim($_POST['module_name']);
    $desc = trim($_POST['description']);

    $sql = "INSERT INTO modules (module_name, description) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $desc);
    $stmt->execute();
    $stmt->close();

    $_SESSION['message'] = "Module added successfully!";
    header("Location: MAINTENANCE.php?tab=modules");
    exit();
}

// UPDATE existing module
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = trim($_POST['module_name']);
    $desc = trim($_POST['description']);

    $sql = "UPDATE modules SET module_name=?, description=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $desc, $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['message'] = "Module updated successfully!";
    header("Location: MAINTENANCE.php?tab=modules");
    exit();
}

// DELETE module
if (isset($_POST['delete'])) {
    $id = $_POST['id'];

    $sql = "DELETE FROM modules WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['message'] = "Module deleted successfully!";
    header("Location: MAINTENANCE.php?tab=modules");
    exit();
}

// FETCH all modules
$result = $conn->query("SELECT * FROM modules ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Module Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="p-4">

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h4 class="mb-4">Module Management</h4>

        <!-- Button to Open Add Modal -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modules_addModal">
            Add New Module
        </button>
    </div>

    <!-- Modules Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Module Name</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['module_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modules_editModal<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modules_deleteModal<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="modules_editModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $row['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel<?= $row['id'] ?>">Edit Module</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <div class="mb-3">
                                    <label>Module Name</label>
                                    <input type="text" name="module_name" class="form-control" value="<?= htmlspecialchars($row['module_name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label>Description</label>
                                    <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($row['description']) ?>">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="update" class="btn btn-success">Update</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Delete Modal -->
            <div class="modal fade" id="modules_deleteModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $row['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel<?= $row['id'] ?>">Delete Module</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to delete module "<strong><?= htmlspecialchars($row['module_name']) ?></strong>"?
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="modules_addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add New Module</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Module Name</label>
                        <input type="text" name="module_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="add" class="btn btn-primary">Add Module</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Success Message Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="messageModalLabel">Success</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?= htmlspecialchars($message) ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<?php if ($message): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    new bootstrap.Modal(document.getElementById('messageModal')).show();
});
</script>
<?php endif; ?>

</body>
</html>