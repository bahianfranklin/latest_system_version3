<?php
require 'db.php';

// ✅ Buffer output to prevent "headers already sent" errors
ob_start();

// ✅ Add Tax Category
if (isset($_POST['add_tax'])) {
    $stmt = $conn->prepare("INSERT INTO tax_categories (code, tax_category) VALUES (?, ?)");
    $stmt->bind_param("ss", $_POST['code'], $_POST['tax_category']);
    $stmt->execute();
    header("Location: maintenance.php?tab=tax");
    exit;
}

// ✅ Update Tax Category
if (isset($_POST['update_tax'])) {
    $stmt = $conn->prepare("UPDATE tax_categories SET code=?, tax_category=? WHERE id=?");
    $stmt->bind_param("ssi", $_POST['code'], $_POST['tax_category'], $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=tax");
    exit;
}

// ✅ Delete Tax Category
if (isset($_POST['delete_tax'])) {
    $stmt = $conn->prepare("DELETE FROM tax_categories WHERE id=?");
    $stmt->bind_param("i", $_POST['id']);
    $stmt->execute();
    header("Location: maintenance.php?tab=tax");
    exit;
}

// ✅ Fetch Tax List
$result = $conn->query("SELECT * FROM tax_categories ORDER BY tax_category ASC");

// ✅ End buffering
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

    <div class="d-flex justify-content-between mb-2">
        <h5>Tax Categories</h5>
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addTaxModal">Add Tax</button>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr><th>ID</th><th>Code</th><th>Category</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['code'] ?></td>
                <td><?= $row['tax_category'] ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editTax<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteTax<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editTax<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="tax.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Edit Tax</h5></div>
                            <div class="modal-body">
                                <input type="text" name="code" value="<?= $row['code'] ?>" class="form-control mb-2" required>
                                <input type="text" name="tax_category" value="<?= $row['tax_category'] ?>" class="form-control mb-2" required>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" name="update_tax" class="btn btn-warning">Update</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Modal -->
            <div class="modal fade" id="deleteTax<?= $row['id'] ?>">
                <div class="modal-dialog">
                    <form method="POST" action="tax.php">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="modal-content">
                            <div class="modal-header"><h5>Delete Tax</h5></div>
                            <div class="modal-body">Delete <b><?= $row['tax_category'] ?></b>?</div>
                            <div class="modal-footer">
                                <button type="submit" name="delete_tax" class="btn btn-danger">Delete</button>
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
    <div class="modal fade" id="addTaxModal">
        <div class="modal-dialog">
            <form method="POST" action="tax.php">
                <div class="modal-content">
                    <div class="modal-header"><h5>Add Tax</h5></div>
                    <div class="modal-body">
                        <input type="text" name="code" placeholder="Code" class="form-control mb-2" required>
                        <input type="text" name="tax_category" placeholder="Tax Category" class="form-control mb-2" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_tax" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
