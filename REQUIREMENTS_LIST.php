<?php
require 'db.php';

// 🔹 Handle Add
if (isset($_POST['add_requirement'])) {
  $name = $_POST['requirement_name'];
  $desc = $_POST['description'];
  $stmt = $conn->prepare("INSERT INTO requirements (requirement_name, description) VALUES (?, ?)");
  $stmt->bind_param("ss", $name, $desc);
  $stmt->execute();
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// 🔹 Handle Edit
if (isset($_POST['edit_requirement'])) {
  $id = $_POST['id'];
  $name = $_POST['requirement_name'];
  $desc = $_POST['description'];
  $status = $_POST['is_active'];
  $stmt = $conn->prepare("UPDATE requirements SET requirement_name=?, description=?, is_active=? WHERE id=?");
  $stmt->bind_param("ssii", $name, $desc, $status, $id);
  $stmt->execute();
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

// 🔹 Handle Delete
if (isset($_POST['delete_requirement'])) {
  $id = $_POST['id'];
  $stmt = $conn->prepare("DELETE FROM requirements WHERE id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

$query = $conn->query("SELECT * FROM requirements ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
  <title>201 File Maintenance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="p-4 bg-light">

<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Requirements Maintenance</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
      + Add Requirement
    </button>
  </div>

  <!-- 🔹 Requirements Table -->
  <table class="table table-bordered table-hover bg-white">
    <thead class="table-secondary">
      <tr>
        <th>#</th>
        <th>Requirement Name</th>
        <th>Description</th>
        <th>Status</th>
        <th width="180">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $query->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['requirement_name']) ?></td>
          <td><?= htmlspecialchars($row['description']) ?></td>
          <td>
            <?= $row['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>' ?>
          </td>
          <td>
            <button class="btn btn-warning btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#editModal"
                    data-id="<?= $row['id'] ?>"
                    data-name="<?= htmlspecialchars($row['requirement_name']) ?>"
                    data-desc="<?= htmlspecialchars($row['description']) ?>"
                    data-status="<?= $row['is_active'] ?>">
              Edit
            </button>
            <button class="btn btn-danger btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteModal"
                    data-id="<?= $row['id'] ?>"
                    data-name="<?= htmlspecialchars($row['requirement_name']) ?>">
              Delete
            </button>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

<!-- 🔸 Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Requirement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label>Requirement Name</label>
          <input type="text" name="requirement_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Description</label>
          <textarea name="description" class="form-control"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="add_requirement" class="btn btn-success">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- 🔸 Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Requirement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="edit_id">
        <div class="mb-3">
          <label>Requirement Name</label>
          <input type="text" name="requirement_name" id="edit_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Description</label>
          <textarea name="description" id="edit_desc" class="form-control"></textarea>
        </div>
        <div class="mb-3">
          <label>Status</label>
          <select name="is_active" id="edit_status" class="form-select">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="edit_requirement" class="btn btn-warning">Update</button>
      </div>
    </form>
  </div>
</div>

<!-- 🔸 Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-danger">Delete Requirement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="delete_id">
        <p>Are you sure you want to delete <strong id="delete_name"></strong>?</p>
      </div>
      <div class="modal-footer">
        <button type="submit" name="delete_requirement" class="btn btn-danger">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Edit Modal
  const editModal = document.getElementById('editModal');
  editModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('edit_id').value = button.getAttribute('data-id');
    document.getElementById('edit_name').value = button.getAttribute('data-name');
    document.getElementById('edit_desc').value = button.getAttribute('data-desc');
    document.getElementById('edit_status').value = button.getAttribute('data-status');
  });

  // Delete Modal
  const deleteModal = document.getElementById('deleteModal');
  deleteModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('delete_id').value = button.getAttribute('data-id');
    document.getElementById('delete_name').textContent = button.getAttribute('data-name');
  });
});
</script>

</body>
</html>
