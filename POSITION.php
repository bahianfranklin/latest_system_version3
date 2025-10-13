<?php
require 'db.php';

// ✅ Handle Add
if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO positions (position) VALUES (?)");
    $stmt->bind_param("s", $_POST['position']);
    $stmt->execute();
    header("Location: maintenance.php?tab=position"); // reload after insert
    exit;
}

// ✅ Handle Update
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE positions SET position=? WHERE id=?");
    $stmt->bind_param("si", $_POST['position'], $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=position"); // reload after update
    exit;
}


// ✅ Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM positions WHERE id=?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=position"); // reload after delete
    exit;
}

// ✅ Fetch all
$result = $conn->query("SELECT * FROM positions ORDER BY position ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Position Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

    <div class="d-flex justify-content-between mb-2">
        <h5>Position List</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPositionModal">Add Position</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr><th>ID</th><th>Position</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['position'] ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editPosition<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deletePosition<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editPosition<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="position.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Edit Position</h5></div>
                            <div class="modal-body">
                                <input type="text" name="position" value="<?= $row['position'] ?>" class="form-control mb-2" required>
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
            <div class="modal fade" id="deletePosition<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="position.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Delete Position</h5></div>
                            <div class="modal-body">Are you sure to delete <b><?= $row['position'] ?></b>?</div>
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
    <div class="modal fade" id="addPositionModal">
        <div class="modal-dialog">
            <form method="POST" action="position.php">
                <div class="modal-content">
                    <div class="modal-header"><h5>Add Position</h5></div>
                    <div class="modal-body">
                        <input type="text" name="position" placeholder="Position Name" class="form-control mb-2" required>
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
