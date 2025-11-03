<?php
require 'db.php';

// 🔹 Handle Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $requirement_id = $_POST['requirement_id'];
    $uploaded_by = 'Admin'; // or $_SESSION['username']

    // 🧹 Delete old file if it exists before uploading new one
    $oldFile = $conn->prepare("SELECT file_path FROM user_requirements WHERE user_id = ? AND requirement_id = ?");
    $oldFile->bind_param("ii", $user_id, $requirement_id);
    $oldFile->execute();
    $oldResult = $oldFile->get_result();
    if ($oldResult && $oldResult->num_rows > 0) {
        $oldPath = $oldResult->fetch_assoc()['file_path'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }
    $oldFile->close();

    // 🗂 File upload setup
    $target_dir = "uploads/requirements/$user_id/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    $file_name = time() . '_' . basename($_FILES["file"]["name"]);
    $file_path = $target_dir . $file_name;
    move_uploaded_file($_FILES["file"]["tmp_name"], $file_path);

    // 💾 Insert or update record
    $stmt = $conn->prepare("
        INSERT INTO user_requirements (user_id, requirement_id, file_path, uploaded_by, status)
        VALUES (?, ?, ?, ?, 'Pending')
        ON DUPLICATE KEY UPDATE 
            file_path = VALUES(file_path),
            date_uploaded = CURRENT_TIMESTAMP,
            uploaded_by = VALUES(uploaded_by)
    ");
    if ($stmt) {
        $stmt->bind_param("iiss", $user_id, $requirement_id, $file_path, $uploaded_by);
        $stmt->execute();
        $stmt->close();
    } else {
        die("Prepare failed: " . $conn->error);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// 🔹 Fetch all users
$users = $conn->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name ASC");

// 🔹 Fetch all requirements (for modal dropdown)
function getRequirements($conn) {
    return $conn->query("SELECT id, requirement_name FROM requirements WHERE is_active = 1");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>201 File Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">

<div class="container">
  <h4 class="mb-4 t fw-bold">201 File - Requirements per User</h4>

  <?php while ($user = $users->fetch_assoc()): ?>
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= htmlspecialchars($user['name']) ?></h5>
        <button class="btn btn-light btn-sm upload-btn" 
                data-bs-toggle="modal" 
                data-bs-target="#uploadModal" 
                data-userid="<?= $user['id'] ?>"
                data-reqid=""
                data-filename="">
          + Upload Requirement
        </button>
      </div>
      <div class="card-body">
        <?php
        $reqQuery = $conn->prepare("
            SELECT r.requirement_name, ur.status, ur.date_uploaded, ur.uploaded_by, ur.file_path, ur.id AS user_req_id, r.id AS req_id
            FROM requirements r
            LEFT JOIN user_requirements ur 
            ON ur.requirement_id = r.id AND ur.user_id = ?
        ");
        $reqQuery->bind_param("i", $user['id']);
        $reqQuery->execute();
        $reqResult = $reqQuery->get_result();
        ?>

        <table class="table table-bordered table-sm align-middle text-center mb-0">
          <thead class="table-secondary">
            <tr>
              <th>Requirement</th>
              <th>Status</th>
              <th>Date Uploaded</th>
              <th>Uploaded By</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $reqResult->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['requirement_name']) ?></td>
              <td><?= $row['status'] ?? 'Not Uploaded' ?></td>
              <td><?= $row['date_uploaded'] ?? '-' ?></td>
              <td><?= $row['uploaded_by'] ?? '-' ?></td>
              <td>
                <?php if ($row['file_path']): ?>
                  <a href="<?= $row['file_path'] ?>" target="_blank" class="btn btn-info btn-sm">View</a>
                <?php endif; ?>
                <button class="btn btn-warning btn-sm upload-btn"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadModal"
                        data-userid="<?= $user['id'] ?>"
                        data-reqid="<?= $row['req_id'] ?>"
                        data-filename="<?= basename($row['file_path'] ?? '') ?>">
                  <?= $row['file_path'] ? 'Edit' : 'Upload' ?>
                </button>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<!-- 🔹 Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Upload User Requirement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" id="modal_user_id">

          <div class="mb-3">
            <label class="form-label">Select Requirement</label>
            <select name="requirement_id" id="modal_requirement_id" class="form-select" required>
              <option value="">-- Select --</option>
              <?php 
              $reqList = getRequirements($conn);
              while ($req = $reqList->fetch_assoc()): ?>
                <option value="<?= $req['id'] ?>"><?= htmlspecialchars($req['requirement_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Upload File</label>
            <input type="file" name="file" id="modal_file_input" class="form-control" required>
            <div id="current_file" class="mt-2 text-muted small"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Upload</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.upload-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('modal_user_id').value = this.dataset.userid;
    document.getElementById('modal_requirement_id').value = this.dataset.reqid || '';
    const filename = this.dataset.filename;
    const currentFile = document.getElementById('current_file');
    currentFile.textContent = filename ? "Current file: " + filename : "";
  });
});
</script>

</body>
</html>
