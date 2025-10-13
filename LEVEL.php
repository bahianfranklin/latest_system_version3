<?php
require 'db.php';

// ✅ Handle Add
if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO levels (level) VALUES (?)");
    $stmt->bind_param("s", $_POST['level']);
    $stmt->execute();
    header("Location: maintenance.php?tab=level"); // reload after insert
    exit;
}

// ✅ Handle Update
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE levels SET level=? WHERE id=?");
    $stmt->bind_param("si", $_POST['level'], $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=level"); // reload after update
    exit;
}

// ✅ Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM levels WHERE id=?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=level"); // reload after delete
    exit;
}

$result = $conn->query("SELECT * FROM levels ORDER BY level ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Branch Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

    <div class="d-flex justify-content-between mb-2">
        <h5>Branch List</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLevelModal">Add Level</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr><th>ID</th><th>Level</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['level'] ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editLevel<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteLevel<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            
            <div class="modal fade" id="editLevel<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="level.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Edit Level</h5></div>
                            <div class="modal-body">
                                <input type="text" name="level" value="<?= $row['level'] ?>" class="form-control mb-2" required>
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

            <div class="modal fade" id="deleteLevel<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="level.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Delete Level</h5></div>
                            <div class="modal-body">Are you sure to delete <b><?= $row['level'] ?></b>?</div>
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
    <div class="modal fade" id="addLevelModal">
        <div class="modal-dialog">
            <form method="POST" action="level.php">
                <div class="modal-content">
                    <div class="modal-header"><h5>Add Level</h5></div>
                    <div class="modal-body">
                        <input type="text" name="level" placeholder="Level Name" class="form-control mb-2" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ✅ Needed for modal to work -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
