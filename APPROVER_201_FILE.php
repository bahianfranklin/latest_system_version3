<?php
require 'db.php';

// Detect standalone vs included
$isStandalone = realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME']);

// 🔹 Handle Approve
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve'])) {
    $user_req_id = intval($_POST['user_req_id']);
    $approved_by = 'Admin'; // or $_SESSION['username']

    $stmt = $conn->prepare("
        UPDATE user_requirements 
        SET status = 'Approved',
            approved_by = ?,
            approval_date = CURRENT_TIMESTAMP
        WHERE id = ?
    ");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $approved_by, $user_req_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to avoid re-submission
    header("Location: APPROVER_201_FILE.php");
    exit;
}

// 🔹 Handle Upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $user_id = $_POST['user_id'];
    $requirement_id = $_POST['requirement_id'];
    $uploaded_by = 'Admin'; // or $_SESSION['username']

    // 🧹 Delete old file if it exists
    $oldFile = $conn->prepare("SELECT file_path FROM user_requirements WHERE user_id = ? AND requirement_id = ?");
    $oldFile->bind_param("ii", $user_id, $requirement_id);
    $oldFile->execute();
    $oldResult = $oldFile->get_result();
    if ($oldResult && $oldResult->num_rows > 0) {
        $oldPath = $oldResult->fetch_assoc()['file_path'];
        if (!empty($oldPath)) {
            $oldPathFs = __DIR__ . DIRECTORY_SEPARATOR . $oldPath;
            if (file_exists($oldPathFs)) {
                @unlink($oldPathFs);
            }
        }
    }
    $oldFile->close();

    // 🗂 File upload setup
    $target_dir_rel = "uploads/requirements/$user_id/";
    $target_dir_fs = __DIR__ . DIRECTORY_SEPARATOR . $target_dir_rel;
    if (!file_exists($target_dir_fs)) mkdir($target_dir_fs, 0777, true);

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        die('File upload error. Error code: ' . ($_FILES['file']['error'] ?? 'none'));
    }

    $file_name = time() . '_' . basename($_FILES["file"]["name"]);
    $file_path_rel = $target_dir_rel . $file_name;
    $file_path_fs = $target_dir_fs . $file_name;

    if (!move_uploaded_file($_FILES["file"]["tmp_name"], $file_path_fs)) {
        die('Failed to move uploaded file. Check permissions on uploads/ directory.');
    }

    // 💾 Insert or update record
    $stmt = $conn->prepare("
        INSERT INTO user_requirements (user_id, requirement_id, file_path, uploaded_by, status)
        VALUES (?, ?, ?, ?, 'Pending')
        ON DUPLICATE KEY UPDATE 
            file_path = VALUES(file_path),
            date_uploaded = CURRENT_TIMESTAMP,
            uploaded_by = VALUES(uploaded_by),
            status = 'Pending'
    ");
    if ($stmt) {
        $stmt->bind_param("iiss", $user_id, $requirement_id, $file_path_rel, $uploaded_by);
        $stmt->execute();
        $stmt->close();
    } else {
        die("Prepare failed: " . $conn->error);
    }

    header("Location: APPROVER_201_FILE.php");
    exit;
}

// 🔹 Fetch all users
$users = $conn->query("SELECT id, name FROM users WHERE status = 'active' ORDER BY name ASC");

// 🔹 Fetch all requirements (for modal dropdown)
function getRequirements($conn) {
    return $conn->query("SELECT id, requirement_name FROM requirements WHERE is_active = 1");
}
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <div class="container">
            <br>
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
                        <tr class="<?= $row['status'] == 'Approved' ? 'table-success' : '' ?>">
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

                            <?php if ($row['file_path'] && $row['status'] != 'Approved'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="user_req_id" value="<?= $row['user_req_id'] ?>">
                                <button type="submit" name="approve" class="btn btn-success btn-sm">Approve</button>
                            </form>
                            <?php endif; ?>
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
                    <form method="POST" enctype="multipart/form-data" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Upload User Requirement</h5>
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
                            <input type="file" name="file" id="modal_file_input" class="form-control">
                            <div id="current_file" class="mt-2 text-muted small"></div>
                        </div>
                        </div>
                        <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Upload</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php include __DIR__ . '/layout/FOOTER.php'; ?>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const uploadModal = document.getElementById('uploadModal');
                    if (!uploadModal) return;

                    uploadModal.addEventListener('show.bs.modal', function (event) {
                        const button = event.relatedTarget;
                        if (!button) return;

                        const userId = button.getAttribute('data-userid') || '';
                        const reqId = button.getAttribute('data-reqid') || '';
                        const filename = button.getAttribute('data-filename') || '';

                        document.getElementById('modal_user_id').value = userId;

                        const sel = document.getElementById('modal_requirement_id');
                        if (reqId && sel.querySelector(`option[value="${reqId}"]`)) {
                            sel.value = reqId;
                        } else {
                            sel.value = '';
                        }

                        const currentFile = document.getElementById('current_file');
                        const fileInput = document.getElementById('modal_file_input');
                        const modalTitle = uploadModal.querySelector('.modal-title');
                        const submitBtn = uploadModal.querySelector('button[type="submit"]');

                        if (filename) {
                            currentFile.textContent = 'Current file: ' + filename;
                            fileInput.removeAttribute('required');
                            modalTitle.textContent = 'Edit User Requirement';
                            submitBtn.textContent = 'Save Changes';
                        } else {
                            currentFile.textContent = '';
                            fileInput.setAttribute('required', 'required');
                            modalTitle.textContent = 'Upload User Requirement';
                            submitBtn.textContent = 'Upload';
                        }
                    });
                });
            </script>
        </div>
    </main>
</div>
