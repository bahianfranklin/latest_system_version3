<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

// ✅ Handle Add
if (isset($_POST['add'])) {
    $role_name = trim($_POST['role_name']);
    $description = trim($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $role_name, $description);
    $stmt->execute();

    header("Location: MAINTENANCE.php?tab=roles");
    exit;
}

// ✅ Handle Update
if (isset($_POST['update'])) {
    $role_name = trim($_POST['role_name']);
    $description = trim($_POST['description']);
    $id = (int)$_POST['id'];

    $stmt = $conn->prepare("UPDATE roles SET role_name=?, description=? WHERE id=?");
    $stmt->bind_param("ssi", $role_name, $description, $id);
    $stmt->execute();

    header("Location: MAINTENANCE.php?tab=roles");
    exit;
}

// ✅ Handle Delete
if (isset($_POST['delete'])) {
    $id = (int)$_POST['id'];

    $stmt = $conn->prepare("DELETE FROM roles WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: MAINTENANCE.php?tab=roles");
    exit;
}

// ✅ Fetch all roles
$result = $conn->query("SELECT * FROM roles ORDER BY role_name ASC");
$roles = $result->fetch_all(MYSQLI_ASSOC);
?><div class="container mt-4">

    <div class="d-flex justify-content-between mb-2">
        <h5>Role List</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">Add Role</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Role Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $row): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['role_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                            data-bs-target="#editRole<?= $row['id'] ?>">Edit</button>

                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                            data-bs-target="#deleteRole<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editRole<?= $row['id'] ?>" tabindex="-1" aria-labelledby="editRoleLabel<?= $row['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="ROLE.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Edit Role</h5></div>
                            <div class="modal-body">
                                <input type="text" name="role_name" value="<?= htmlspecialchars($row['role_name']) ?>" class="form-control mb-2" required>
                                <textarea name="description" class="form-control" placeholder="Description"><?= htmlspecialchars($row['description']) ?></textarea>
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
            <div class="modal fade" id="deleteRole<?= $row['id'] ?>" tabindex="-1" aria-labelledby="deleteRoleLabel<?= $row['id'] ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="ROLE.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Delete Role</h5></div>
                            <div class="modal-body">Are you sure to delete <b><?= htmlspecialchars($row['role_name']) ?></b>?</div>
                            <div class="modal-footer">
                                <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Add Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="ROLE.php">
                <div class="modal-content">
                    <div class="modal-header"><h5>Add Role</h5></div>
                    <div class="modal-body">
                        <input type="text" name="role_name" placeholder="Role Name" class="form-control mb-2" required>
                        <textarea name="description" placeholder="Description" class="form-control"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
