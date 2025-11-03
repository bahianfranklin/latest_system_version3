<?php
require 'db.php';

// ==================== HANDLE FORM ACTIONS ====================

// 🔹 Add Salary
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $user_id = $_POST['user_id'];
    $monthly_rate = $_POST['monthly_rate'];

    $stmt = $conn->prepare("INSERT INTO user_salary_rates (user_id, monthly_rate) VALUES (?, ?)");
    $stmt->bind_param("id", $user_id, $monthly_rate);
    $stmt->execute();
}

// 🔹 Update Salary
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['id'];
    $monthly_rate = $_POST['monthly_rate'];

    $stmt = $conn->prepare("UPDATE user_salary_rates SET monthly_rate = ? WHERE id = ?");
    $stmt->bind_param("di", $monthly_rate, $id);
    $stmt->execute();
}

// 🔹 Delete Salary
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    $stmt = $conn->prepare("DELETE FROM user_salary_rates WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// ==================== FETCH DATA ====================
$userQuery = $conn->query("SELECT id, name FROM users");
$salaryQuery = $conn->query("
    SELECT s.*, u.name 
    FROM user_salary_rates s 
    JOIN users u ON s.user_id = u.id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HR Salary Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">HR Salary Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSalaryModal">
            + Add Salary
        </button>
    </div>

    <!-- Salary Table -->
    <table class="table table-bordered table-striped text-center align-middle">
        <thead class="table-dark">
            <tr>
                <th>User</th>
                <th>Monthly Rate</th>
                <th>Periodic Rate</th>
                <th>Daily Rate</th>
                <th>Hourly Rate</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $salaryQuery->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td>₱<?= number_format($row['monthly_rate'], 2) ?></td>
                <td>₱<?= number_format($row['monthly_rate'] / 2, 2) ?></td>
                <td>₱<?= number_format($row['monthly_rate'] / 22, 2) ?></td>
                <td>₱<?= number_format(($row['monthly_rate'] / 22) / 8, 2) ?></td>
                <td>
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editSalaryModal<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteSalaryModal<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- 🟡 Edit Modal -->
            <div class="modal fade" id="editSalaryModal<?= $row['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Salary</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <label>User</label>
                                <input type="text" value="<?= htmlspecialchars($row['name']) ?>" class="form-control mb-3" readonly>

                                <label>Monthly Rate</label>
                                <input type="number" step="0.01" name="monthly_rate" value="<?= $row['monthly_rate'] ?>" class="form-control" required>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 🔴 Delete Modal -->
            <div class="modal fade" id="deleteSalaryModal<?= $row['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <div class="modal-header">
                                <h5 class="modal-title text-danger">Delete Salary Record</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                Are you sure you want to delete <b><?= htmlspecialchars($row['name']) ?></b>’s salary record?
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- 🟢 Add Modal -->
<div class="modal fade" id="addSalaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Add Salary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label>Select User</label>
                    <select name="user_id" class="form-control mb-3" required>
                        <option value="">Select a user</option>
                        <?php 
                        $userQuery->data_seek(0);
                        while ($u = $userQuery->fetch_assoc()): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Monthly Rate</label>
                    <input type="number" step="0.01" name="monthly_rate" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add Salary</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
