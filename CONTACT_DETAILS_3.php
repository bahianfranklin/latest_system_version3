<?php
session_start();

require_once __DIR__ . '/CONFIG_2.php';
require_once __DIR__ . '/WEBLIB.php';

/* ===============================
   SESSION / AUTH SETUP
================================ */
$user_id = $_SESSION['user_id'] ?? 0;

// Your system does NOT use access_token, so we fake it for WebLib
$accessToken = "SYSTEM_LOGIN";

if (!$user_id) {
    die("Unauthorized");
}

// SINGLE WebLib instance (use this everywhere below)
$web = new WebLib();

/* ===============================
   API BASE URL
================================ */
$baseUrl = "https://api.mandbox.com/apitest/v1/contact.php?action=view";

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

        header("Location: CONTACT_DETAILS_3.php?success=1");
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

        header("Location: CONTACT_DETAILS_3.php?updated=1");
        exit;
    }

    if ($action === 'delete') {
        $web->requestURL($baseUrl, [
            "action"    => "delete",
            "record_id" => $_POST['id'] ?? ''
        ], $accessToken, $user_id);

        header("Location: CONTACT_DETAILS_3.php?deleted=1");
        exit;
    }
}

/* ===============================
   READ DATA
================================ */

try {
    echo "BASE URL: $baseUrl\n";
    // Try "contacts" as the action – adjust if needed (reference uses "view", but keep as is for WebLib)
    $web->requestURL($baseUrl, ["action" => "view", "user_id" => $user_id], $accessToken, $user_id);
    
    // Use WebLib methods for parsing
    $status = $web->status();
    $message = $web->message();
    $code = $web->code();
    echo "Status: $status, Message: $message, Code: $code\n";
    if ($status !== 'yes') {
        throw new Exception("API error: " . ($message ?? 'Unknown error') . " (Code: " . ($code ?? 'N/A') . ")");
    }
    
    // Get records from WebLib
    $records = $web->resultData() ?? [];
    if (!is_array($records)) {
        $records = [];
    }
} catch (Exception $e) {
    $records = [];
    $apiError = $e->getMessage();
}

// Fallback: Mock data for testing if API doesn't support reads (uncomment to use)
if (empty($records) && !isset($apiError)) {
    $records = [
        ["id" => 1, "fullname" => "John Doe", "address" => "123 Main St", "contact_no" => "123-456-7890"],
        ["id" => 2, "fullname" => "Jane Smith", "address" => "456 Elm St", "contact_no" => "987-654-3210"],
        // Add more sample records as needed
    ];
}

/* ===============================
   DEBUG OUTPUT (Temporary - Remove in production)
================================ */
echo "<pre>";
echo "DEBUG INFO:\n";
echo "User ID: $user_id\n";
echo "API URL: $baseUrl\n";
echo "Action Used: contacts\n";  // Update this if you change the action
echo "WebLib Status: " . ($web->status() ?? 'N/A') . "\n";
echo "WebLib Message: " . ($web->message() ?? 'N/A') . "\n";
echo "WebLib Code: " . ($web->code() ?? 'N/A') . "\n";
echo "RAW RESPONSE:\n";
print_r($web->getRawResponse());
echo "\n\nRECORDS ARRAY:\n";
print_r($records);
if (isset($apiError)) {
    echo "\n\nAPI ERROR: $apiError\n";
}
echo "</pre>";
// exit;  // Uncomment this if you want to stop here for debugging

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
$extraHead = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">\n<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
?>

<?php include __DIR__ . '/layout/HEADER.php'; ?>
<?php include __DIR__ . '/layout/NAVIGATION.php'; ?>

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
                <a href="CONTACT_DETAILS_3.php" class="btn btn-secondary ms-2">Reset</a>
            </form>

            <?php if (isset($apiError)): ?>
                <div class="alert alert-danger">
                    <strong>API Error:</strong> <?= htmlspecialchars($apiError) ?><br>
                    <strong>Next Steps:</strong> Check if the API is down, verify your access token, or contact support.
                </div>
            <?php elseif (!empty($currentRecords)): ?>
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
                                    <button class="btn btn-info btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#view<?= $row['id'] ?>">
                                        <i class="fa fa-eye"></i>
                                    </button>
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

                <!-- Pagination (enhanced from reference) -->
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                    <nav>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page-1 ?>&limit=<?= urlencode($perPage) ?>&search=<?= urlencode($search) ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $p ?>&limit=<?= urlencode($perPage) ?>&search=<?= urlencode($search) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page+1 ?>&limit=<?= urlencode($perPage) ?>&search=<?= urlencode($search) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <!-- Page Dropdown -->
                    <form method="get" class="d-inline">
                        <input type="hidden" name="limit" value="<?= $perPage ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <label for="pageSelect">Page</label>
                        <select name="page" id="pageSelect" onchange="this.form.submit()">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <option value="<?= $i ?>" <?= ($i == $page) ? 'selected' : '' ?>>Page <?= $i ?> of <?= $totalPages ?></option>
                            <?php endfor; ?>
                        </select>
                    </form>

                    <!-- Entries Dropdown -->
                    <form method="get" class="d-inline">
                        <input type="hidden" name="page" value="<?= $page ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <label>Show 
                            <select name="limit" onchange="this.form.submit()">
                                <option value="5" <?= ($perPage == 5) ? 'selected' : '' ?>>5</option>
                                <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= ($perPage == 25) ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100</option>
                                <option value="all" <?= ($perPage === 'all') ? 'selected' : '' ?>>Show All</option>
                            </select>
                            entries
                        </label>
                    </form>

                    <!-- Export (assuming Export.php exists) -->
                    <form method="get" action="Export.php" class="d-inline">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="page" value="<?= $page ?>">
                        <input type="hidden" name="limit" value="<?= $perPage ?>">
                        <label>Export:
                            <select name="type" onchange="this.form.submit()" class="form-select d-inline w-auto">
                                <option value="">-- Select --</option>
                                <option value="csv">CSV</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </label>
                    </form>

                    <div class="ms-auto text-end mt-2">
                        <small>
                            <?php
                            if ($totalRecords > 0) {
                                $start = $offset + 1;
                                $end = $offset + count($currentRecords);
                                echo "Showing {$start} to {$end} of {$totalRecords} records";
                            } else {
                                echo "Showing 0 to 0 of 0 records";
                            }
                            ?>
                        </small>
                    </div>
                </div>

            <?php else: ?>
                <p class="text-muted">No records found. Possible reasons: No contacts exist for this user, API is down, or data structure mismatch.</p>
            <?php endif; ?>

        </div>
    </main>

    <!-- ===============================
        VIEW MODAL (Added from reference)
    ================================ -->
    <?php foreach ($currentRecords as $row): ?>
        <div class="modal fade" id="view<?= $row['id'] ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5>View Contact</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p><b>Name:</b> <?= htmlspecialchars($row['fullname'] ?? '') ?></p>
                        <p><b>Address:</b> <?= htmlspecialchars($row['address'] ?? '') ?></p>
                        <p><b>Contact:</b> <?= htmlspecialchars($row['contact_no'] ?? '') ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

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