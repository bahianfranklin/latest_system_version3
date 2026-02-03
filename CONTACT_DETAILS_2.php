<?php
session_start();

require_once __DIR__ . '/CONFIG_2.php';
require_once __DIR__ . '/weblib.php';

/* ===============================
   SESSION CHECK
================================ */
$user = $_SESSION['user'] ?? null;
$user_id = $user['id'] ?? 0;

if (!$user_id) {
    die("Unauthorized access");
}

$accessToken = $_SESSION['access_token'] ?? '';

/* ===============================
   API BASE URL
================================ */
$baseUrl = "https://api.mandbox.com/apitest/v1/contact.php";

if (empty($baseUrl)) {
    die("Base URL not defined");
}

$web = new WebLib();

/* ===============================
   FILTERS
================================ */
$search  = $_GET['search'] ?? '';
$perPage = $_GET['limit'] ?? 5;
$page    = max(1, (int)($_GET['page'] ?? 1));

/* ===============================
   HANDLE CRUD (POST)
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $web->requestURL($baseUrl, [
            "action"     => "add",
            "fullname"   => $_POST['fullname'] ?? '',
            "address"    => $_POST['address'] ?? '',
            "contact_no" => $_POST['contact_no'] ?? ''
        ], $accessToken, $user_id);

        header("Location: CONTACT_DETAILS_2.php?success=1");
        exit;
    }

    if ($action === 'edit') {
        $web->requestURL($baseUrl, [
            "action"     => "update",
            "record_id"  => $_POST['id'] ?? '',
            "fullname"   => $_POST['fullname'] ?? '',
            "address"    => $_POST['address'] ?? '',
            "contact_no" => $_POST['contact_no'] ?? ''
        ], $accessToken, $user_id);

        header("Location: CONTACT_DETAILS_2.php?updated=1");
        exit;
    }

    if ($action === 'delete') {
        $web->requestURL($baseUrl, [
            "action"    => "delete",
            "record_id" => $_POST['id'] ?? ''
        ], $accessToken, $user_id);

        header("Location: CONTACT_DETAILS_2.php?deleted=1");
        exit;
    }
}

/* ===============================
   READ DATA (POST to API)
================================ */
$web->requestURL($baseUrl, [
    "action" => "view"
], $accessToken, $user_id);

$response = $web->resultData();
$records = $response->data ?? [];

/*
✅ FIX HERE:
resultData() returns objects (stdClass)
but your view uses $row['field'] (arrays)

So convert objects -> arrays
*/
$records = json_decode(json_encode($records), true);

if (!is_array($records)) {
    $records = [];
}

/* ===============================
   OPTIONAL DEBUG (Uncomment to check API output)
================================ */
// echo "<pre>";
// echo "RAW RESPONSE:\n";
// print_r($web->getRawResponse());
// echo "\n\nDECODED RECORDS:\n";
// var_dump($records);
// echo "</pre>";
// exit;

/* ===============================
   SEARCH FILTER
================================ */
if ($search !== '') {
    $records = array_values(array_filter($records, function ($row) use ($search) {
        $fullname  = $row['fullname'] ?? '';
        $address   = $row['address'] ?? '';
        $contactNo = $row['contact_no'] ?? '';

        return stripos($fullname, $search) !== false ||
               stripos($address, $search) !== false ||
               stripos($contactNo, $search) !== false;
    }));
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
    if ($perPage <= 0) $perPage = 5;

    $totalPages = max(1, (int)ceil($totalRecords / $perPage));
    $page = min($page, $totalPages);
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
                <a href="CONTACT_DETAILS_2.php" class="btn btn-secondary ms-2">Reset</a>
            </form>

            <?php if (!empty($currentRecords)): ?>
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Contact</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($currentRecords as $i => $row): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['fullname'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['address'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['contact_no'] ?? '') ?></td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#edit<?= $row['id'] ?>">
                                        <i class="fa fa-pen"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#delete<?= $row['id'] ?>">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination (optional) -->
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                    <a class="page-link"
                                       href="?page=<?= $p ?>&limit=<?= urlencode($_GET['limit'] ?? 5) ?>&search=<?= urlencode($search) ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <p class="text-muted">No records found.</p>
            <?php endif; ?>

        </div>
    </main>

    <!-- ===============================
        ADD MODAL
    ================================ -->
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

    <!-- ===============================
        EDIT & DELETE MODALS
    ================================ -->
    <?php foreach ($currentRecords as $row): ?>
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
                            <input class="form-control mb-2" name="fullname"
                                   value="<?= htmlspecialchars($row['fullname'] ?? '') ?>" required>
                            <input class="form-control mb-2" name="address"
                                   value="<?= htmlspecialchars($row['address'] ?? '') ?>" required>
                            <input class="form-control" name="contact_no"
                                   value="<?= htmlspecialchars($row['contact_no'] ?? '') ?>" required>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

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
                            Delete <b><?= htmlspecialchars($row['fullname'] ?? '') ?></b>?
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

    <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>
