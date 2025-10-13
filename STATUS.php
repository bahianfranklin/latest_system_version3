<?php
require 'db.php';

// ✅ Handle Add
if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO statuses (status) VALUES (?)");
    $stmt->bind_param("s", $_POST['status']);
    $stmt->execute();
    header("Location: maintenance.php?tab=status"); // reload after insert
    exit;
}

// ✅ Handle Update
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE statuses SET status=? WHERE id=?");
    $stmt->bind_param("si", $_POST['status'], $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=status"); // reload after update
    exit;
}


// ✅ Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM statuses WHERE id=?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=status"); // reload after delete
    exit;
}

// ✅ Fetch all
$result = $conn->query("SELECT * FROM statuses ORDER BY status ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Status Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

    <div class="d-flex justify-content-between mb-2">
        <h5>Status List</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStatusModal">Add Status</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr><th>ID</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStatus<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteStatus<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editStatus<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="status.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Edit Status</h5></div>
                            <div class="modal-body">
                                <input type="text" name="status" value="<?= $row['status'] ?>" class="form-control mb-2" required>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="update" class="btn btn-warning">Update</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Modal -->
            <div class="modal fade" id="deleteStatus<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="status.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Delete Status</h5></div>
                            <div class="modal-body">Are you sure to delete <b><?= $row['status'] ?></b>?</div>
                            <div class="modal-footer">
                                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Add Modal -->
    <div class="modal fade" id="addStatusModal">
        <div class="modal-dialog">
            <form method="POST" action="status.php">
                <div class="modal-content">
                    <div class="modal-header"><h5>Add Status</h5></div>
                    <div class="modal-body">
                        <input type="text" name="status" placeholder="Status Name" class="form-control mb-2" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ✅ Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
