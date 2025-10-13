<?php
require 'db.php';

// ✅ Handle Add
if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO departments (department, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $_POST['department'], $_POST['description']);
    $stmt->execute();
    header("Location: maintenance.php?tab=department"); // reload after insert
    exit;
}

// ✅ Handle Update
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE departments SET department=?, description=? WHERE id=?");
    $stmt->bind_param("ssi", $_POST['department'], $_POST['description'], $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=department"); // reload after update
    exit;
}

// ✅ Handle Delete
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM departments WHERE id=?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=department"); // reload after delete
    exit;
}

$result = $conn->query("SELECT * FROM departments ORDER BY department ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deparment Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

    <div class="d-flex justify-content-between mb-2">
        <h5>Departments List</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adddepartmentModal">Add Department</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr><th>ID</th><th>Department</th><th>description</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['department'] ?></td>
                <td><?= $row['description'] ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editdepartment<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deletedepartment<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editdepartment<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="department.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Edit Department</h5></div>
                            <div class="modal-body">
                                <input type="text" name="department" value="<?= $row['department'] ?>" class="form-control mb-2" required>
                                <input type="text" name="description" value="<?= $row['description'] ?>" class="form-control" required>
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
            <div class="modal fade" id="deletedepartment<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="department.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Delete Department</h5></div>
                            <div class="modal-body">Are you sure to delete <b><?= $row['department'] ?></b>?</div>
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
    <div class="modal fade" id="adddepartmentModal">
        <div class="modal-dialog">
            <form method="POST" action="department.php">
                <div class="modal-content">
                    <div class="modal-header"><h5>Add Department</h5></div>
                    <div class="modal-body">
                        <input type="text" name="department" placeholder="Department" class="form-control mb-2" required>
                        <input type="text" name="description" placeholder="Description" class="form-control" required>
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
