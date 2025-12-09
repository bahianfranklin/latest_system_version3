<?php
session_start();
require 'db.php';
require 'audit.php';
require 'autolock.php';

// ✅ Ensure only logged in users
if (!isset($_SESSION['user'])) {
    header('Location: LOGIN.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// ✅ Handle ADD
if (isset($_POST['add'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $stmt = $conn->prepare("INSERT INTO announcements (title, description, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $title, $description, $user_id);
    $stmt->execute();

    // 🔥 AUDIT TRAIL
    logAction($conn, $user_id, "ADD ANNOUNCEMENT", "Added: $title");

    header("Location: announcements.php");
    exit();
}

// ✅ Handle EDIT
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $stmt = $conn->prepare("UPDATE announcements SET title=?, description=?, updated_at=NOW(), updated_by=? WHERE id=?");
    $stmt->bind_param("ssii", $title, $description, $user_id, $id);
    $stmt->execute();

    // 🔥 AUDIT TRAIL
    logAction($conn, $user_id, "EDIT ANNOUNCEMENT", "Updated ID $id: $title");

    header("Location: announcements.php");
    exit();
}

// ✅ Handle DELETE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // 🔥 AUDIT TRAIL
    logAction($conn, $user_id, "DELETE ANNOUNCEMENT", "Deleted ID $id: $title");

    header("Location: announcements.php");
    exit();
}

// ✅ Fetch Announcements with optional SEARCH
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {

    // 🔥 AUDIT TRAIL
    logAction($conn, $user_id, "SEARCH ANNOUNCEMENT", "Keyword: $search");

    $sql = "
        SELECT a.*, 
            u1.name AS created_name, 
            u2.name AS updated_name
        FROM announcements a
        LEFT JOIN users u1 ON a.created_by = u1.id
        LEFT JOIN users u2 ON a.updated_by = u2.id
        WHERE a.title LIKE ? OR a.description LIKE ?
        ORDER BY a.created_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $likeSearch = '%' . $search . '%';
    $stmt->bind_param("ss", $likeSearch, $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "
        SELECT a.*, 
            u1.name AS created_name, 
            u2.name AS updated_name
        FROM announcements a
        LEFT JOIN users u1 ON a.created_by = u1.id
        LEFT JOIN users u2 ON a.updated_by = u2.id
        ORDER BY a.created_at DESC
    ";
    $result = $conn->query($sql);
}
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container mt-3">
            <h3 class="mb-4"> Announcements</h3>
            <div class="d-flex justify-content-between mb-3">
                <!-- Search Form -->
                <form class="d-flex" method="GET">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search announcement..." 
                        value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-secondary">Search</button>
                    <a href="announcements.php" class="btn btn-outline-secondary ms-2">Clear</a>
                </form>

                <!-- Add Button -->
                <button class="btn btn-primary ms-2" data-bs-toggle="modal" data-bs-target="#addModal">
                    ➕ Add Announcement
                </button>
            </div>

            <!-- Announcements Table -->
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Updated</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                        <td>
                            <?= $row['created_name'] ?><br>
                            <small><?= $row['created_at'] ?></small>
                        </td>
                        <td>
                            <?= $row['updated_name'] ? $row['updated_name'] : '-' ?><br>
                            <small><?= $row['updated_at'] ? $row['updated_at'] : '-' ?></small>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-warning" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                            <a href="?delete=<?= $row['id'] ?>" 
                            class="btn btn-sm btn-danger"
                            onclick="return confirm('Are you sure you want to delete this announcement?')">Delete</a>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">Edit Announcement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <div class="mb-3">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($row['title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($row['description']) ?></textarea>
                            </div>
                            </div>
                            <div class="modal-footer">
                            <button type="submit" name="update" class="btn btn-warning">Update</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                        </div>
                    </div>
                    </div>

                    <?php } ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">Add Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add" class="btn btn-primary">Add</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const body = document.body;
        const sidebarToggle = document.querySelector("#sidebarToggle");

        if (sidebarToggle) {
            sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            body.classList.toggle("sb-sidenav-toggled");
            });
        }
    });
</script>
