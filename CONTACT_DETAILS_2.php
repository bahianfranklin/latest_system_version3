<?php
session_start();

require 'CONFIG_2.php';
require 'weblib.php';

/* ===============================
   SESSION CHECK
================================ */
// $user = $_SESSION['user'] ?? null;
// $user_id = $user['id'] ?? 0;

// if (!$user_id) {
//     die("Unauthorized access");
// }

// $accessToken = $_SESSION['access_token'] ?? '';

// $web = new WebLib();

/* ===============================
   FILTERS (UI ONLY)
================================ */
$search  = $_GET['search'] ?? '';
$perPage = $_GET['limit'] ?? 5;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

/* ===============================
   HANDLE CRUD (POST ONLY)
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ➕ ADD */
    if ($_POST['action'] === 'add') {

        $params = [
            "action"     => "add",
            "fullname"   => $_POST['fullname'],
            "address"    => $_POST['address'],
            "contact_no" => $_POST['contact_no']
        ];

        $web->requestURL($baseUrl, $params, $accessToken, $user_id);

        header("Location: contact_details.php?success=1");
        exit;
    }

    /* ✏️ UPDATE */
    if ($_POST['action'] === 'edit') {

        $params = [
            "action"     => "update",
            "record_id"  => $_POST['id'],
            "fullname"   => $_POST['fullname'],
            "address"    => $_POST['address'],
            "contact_no" => $_POST['contact_no']
        ];

        $web->requestURL($baseUrl, $params, $accessToken, $user_id);

        if ($web->status() !== "yes") {
            die("Update failed: " . $web->message());
        }

        header("Location: contact_details.php?updated=1");
        exit;
    }

    /* 🗑 DELETE */
    if ($_POST['action'] === 'delete') {

        $params = [
            "action"    => "delete",
            "record_id" => $_POST['id']
        ];

        $web->requestURL($baseUrl, $params, $accessToken, $user_id);

        header("Location: contact_details.php?deleted=1");
        exit;
    }
}

/* ===============================
   READ DATA (POST ONLY)
================================ */
$params = [
    "action" => "view"
];

$web->requestURL($baseUrl, $params, $accessToken, $user_id);

$records = $web->resultData() ?? [];

/* ===============================
   SEARCH FILTER (CLIENT SIDE)
================================ */
if (!empty($search)) {
    $records = array_filter($records, function ($row) use ($search) {
        return stripos($row['fullname'], $search) !== false ||
               stripos($row['address'], $search) !== false ||
               stripos($row['contact_no'], $search) !== false;
    });
}

/* ===============================
   PAGINATION
================================ */
$totalRecords = count($records);

if ($perPage === "all") {
    $currentRecords = $records;
    $totalPages = 1;
    $page = 1;
    $offset = 0;
} else {
    $perPage = (int)$perPage;
    $totalPages = max(1, ceil($totalRecords / $perPage));
    $offset = ($page - 1) * $perPage;
    $currentRecords = array_slice($records, $offset, $perPage);
}

$pageTitle = "Contact Details";
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
<main>
<div class="container mt-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Contact Details</h3>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fa fa-plus"></i> Add New
    </button>
</div>

<form method="get" class="d-flex mb-3">
    <input type="text" name="search" class="form-control me-2"
           placeholder="Search name, address, contact"
           value="<?= htmlspecialchars($search) ?>">
    <button class="btn btn-primary">Search</button>
    <a href="contact_details.php" class="btn btn-secondary ms-2">Reset</a>
</form>

<?php if ($currentRecords): ?>
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>Name</th>
    <th>Address</th>
    <th>Contact</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($currentRecords as $i => $row): ?>
<tr>
    <td><?= $offset + $i + 1 ?></td>
    <td><?= htmlspecialchars($row['fullname']) ?></td>
    <td><?= htmlspecialchars($row['address']) ?></td>
    <td><?= htmlspecialchars($row['contact_no']) ?></td>
    <td>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#edit<?= $row['id'] ?>">
            <i class="fa fa-pen"></i>
        </button>
        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delete<?= $row['id'] ?>">
            <i class="fa fa-trash"></i>
        </button>
    </td>
</tr>

<!-- EDIT MODAL -->
<div class="modal fade" id="edit<?= $row['id'] ?>">
<div class="modal-dialog">
<div class="modal-content">
<form method="post">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<div class="modal-header bg-primary text-white">
<h5>Edit Contact</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input class="form-control mb-2" name="fullname" value="<?= htmlspecialchars($row['fullname']) ?>" required>
<input class="form-control mb-2" name="address" value="<?= htmlspecialchars($row['address']) ?>" required>
<input class="form-control" name="contact_no" value="<?= htmlspecialchars($row['contact_no']) ?>" required>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-primary">Update</button>
</div>
</form>
</div>
</div>
</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="delete<?= $row['id'] ?>">
<div class="modal-dialog">
<div class="modal-content">
<form method="post">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<div class="modal-header bg-danger text-white">
<h5>Confirm Delete</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
Delete <b><?= htmlspecialchars($row['fullname']) ?></b>?
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-danger">Delete</button>
</div>
</form>
</div>
</div>
</div>

<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>No records found.</p>
<?php endif; ?>

</div>
</main>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal">
<div class="modal-dialog">
<div class="modal-content">
<form method="post">
<input type="hidden" name="action" value="add">
<div class="modal-header bg-success text-white">
<h5>Add Contact</h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input class="form-control mb-2" name="fullname" placeholder="Full Name" required>
<input class="form-control mb-2" name="address" placeholder="Address" required>
<input class="form-control" name="contact_no" placeholder="Contact No" required>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-success">Save</button>
</div>
</form>
</div>
</div>
</div>

<?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>
