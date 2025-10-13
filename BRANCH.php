<?php
require 'db.php';

// ✅ Handle Add
if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO branches (branch, location) VALUES (?, ?)");
    $stmt->bind_param("ss", $_POST['branch'], $_POST['location']);
    $stmt->execute();
    header("Location: MAINTENANCE.php?tab=branch"); // reload after insert
    exit;
}

// ✅ Handle Update
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE branches SET branch=?, location=? WHERE id=?");
    $stmt->bind_param("ssi", $_POST['branch'], $_POST['location'], $_POST['id']);
    $stmt->execute();
    header("Location: MAINTENANCE.php?tab=branch"); // reload after update
    exit;
}

// ✅ Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM branches WHERE id=?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    header("Location: MAINTENANCE.php?tab=branch"); // reload after delete
    exit;
}

$result = $conn->query("SELECT * FROM branches ORDER BY branch ASC");
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
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBranchModal">Add Branch</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr><th>ID</th><th>Branch</th><th>Location</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['branch'] ?></td>
                <td><?= $row['location'] ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editBranch<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteBranch<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editBranch<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Edit Branch</h5></div>
                            <div class="modal-body">
                                <input type="text" name="branch" value="<?= $row['branch'] ?>" class="form-control mb-2" required>
                                <input type="text" name="location" value="<?= $row['location'] ?>" class="form-control" required>
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
            <div class="modal fade" id="deleteBranch<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Delete Branch</h5></div>
                            <div class="modal-body">Are you sure to delete <b><?= $row['branch'] ?></b>?</div>
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
    <div class="modal fade" id="addBranchModal">
        <div class="modal-dialog">
            <form method="POST" action="">
                <div class="modal-content">
                    <div class="modal-header"><h5>Add Branch</h5></div>
                    <div class="modal-body">
                        <input type="text" name="branch" placeholder="Branch Name" class="form-control mb-2" required>
                        <input type="text" name="location" placeholder="Location" class="form-control" required>
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
