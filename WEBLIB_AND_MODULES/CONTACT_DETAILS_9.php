<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/CONFIG_2.php";   // API_KEY and COMPANY_KEY
require_once __DIR__ . "/WEBLIB.php";

$weblib = new WebLib();

// VIEW: Get all contacts
$urlView = "https://api.mandbox.com/apitest/v1/contact.php?action=view";

// search
$search = $_GET['search'] ?? '';

// per page
$perPage = $_GET['limit'] ?? 5;

// current page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$params = [
    "record_id" => ""
];
//echo "$urlView?" . json_encode($params) . "<br><br>";
$weblib->requestURL($urlView, $params);
$dataList = $weblib->resultData() ?? [];

// echo ($weblib->getRawResponse());
// exit;

// Convert objects to array
$records = json_decode(json_encode($dataList), true);

// Search
if (!empty($search)) {
    $records = array_filter($records, function ($row) use ($search) {
        return stripos($row['fullname'] ?? '', $search) !== false ||
               stripos($row['address'] ?? '', $search) !== false ||
               stripos($row['contact_no'] ?? '', $search) !== false;
    });

    // Reset array keys after filtering
    $records = array_values($records);
}

// totals
$totalRecords = count($records);

// Pagination math
if ($perPage === "search") {
    $currentRecords = $records;
    $totalPages = 1;
    $page = 1;
    $start_from = 0;
} else {
    $perPage = intval($perPage);
    $totalPages = ceil($totalRecords / $perPage);
    $start_from = ($page - 1) * $perPage;
    $currentRecords = array_slice($records, $start_from, $perPage);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contacts CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">

    <div class="container">
        <h2>Contacts List</h2>

        <div class="row mb-3 align-items-center">

            <!-- Search (Left Side) -->
            <div class="col-md-6">
                <form method="get" class="d-flex">
                    <input type="text" name="search"
                        class="form-control me-2"
                        placeholder="Search name, address or contact..."
                        value="<?= htmlspecialchars($search) ?>">

                    <input type="hidden" name="limit" value="<?= $perPage ?>">

                    <button type="submit" class="btn btn-primary me-2">
                        Search
                    </button>

                    <?php if (!empty($search)): ?>
                        <a href="?" class="btn btn-secondary">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Add Button (Right Side) -->
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <button class="btn btn-success"
                    data-bs-toggle="modal"
                    data-bs-target="#addModal">
                    Add Contact
                </button>
            </div>

        </div>

        <?php if (!empty($currentRecords)): ?>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Address</th>
                        <th>Contact No</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = $offset + 1;
                    foreach ($currentRecords as $data): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($data['fullname'] ?? '') ?></td>
                            <td><?= htmlspecialchars($data['address'] ?? '') ?></td>
                            <td><?= htmlspecialchars($data['contact_no'] ?? '') ?></td>
                            <td>
                                <button class="btn btn-primary btn-sm editBtn" data-id="<?= $data['id'] ?>"
                                    data-fullname="<?= htmlspecialchars($data['fullname']) ?>"
                                    data-address="<?= htmlspecialchars($data['address']) ?>"
                                    data-contact="<?= htmlspecialchars($data['contact_no']) ?>" data-bs-toggle="modal"
                                    data-bs-target="#editModal">Edit</button>
                                <button class="btn btn-danger btn-sm deleteBtn" data-id="<?= $data['id'] ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No records found.</p>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-between mt-3 flex-wrap">
        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link"
                            href="?page=<?= $page - 1 ?>&limit=<?= $perPage ?>&search=<?= $search ?>">Prev</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&limit=<?= $perPage ?>&search=<?= $search ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link"
                            href="?page=<?= $page + 1 ?>&limit=<?= $perPage ?>&search=<?= $search ?>">Next</a>
                    </li>
                <?php endif; ?>

            </ul>
        </nav>

        <!-- Show Entries -->
        <form method="get">
            Show
            <select name="limit" onchange="this.form.submit()">
                <option value="5" <?= ($perPage == 5) ? 'selected' : '' ?>>5</option>
                <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10</option>
                <option value="25" <?= ($perPage == 25) ? 'selected' : '' ?>>25</option>
                <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50</option>
                <option value="all" <?= ($perPage === 'all') ? 'selected' : '' ?>>All</option>
            </select>
            entries

            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="page" value="1">
        </form>

        <!-- Showing X to Y -->
        <small>
            <?php
            $start = $offset + 1;
            $end = $offset + count($currentRecords);
            echo "Showing $start to $end of $totalRecords records";
            ?>
        </small>
    </div>


    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="addForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Contact</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="fullname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Contact No</label>
                            <input type="text" name="contact_no" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Add</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Contact</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="record_id" id="edit_id">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Address</label>
                            <input type="text" name="address" id="edit_address" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Contact No</label>
                            <input type="text" name="contact_no" id="edit_contact" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function () {

            // Open edit modal and populate values
            $('.editBtn').click(function () {
                $('#edit_id').val($(this).data('id'));
                $('#edit_fullname').val($(this).data('fullname'));
                $('#edit_address').val($(this).data('address'));
                $('#edit_contact').val($(this).data('contact'));
            });

            // Handle Add form submit
            $('#addForm').submit(function (e) {
                e.preventDefault();
                $.post('CRUD.php?action=add', $(this).serialize(), function (response) {
                    alert(response);
                    location.reload();
                });
            });

            // Handle Edit form submit
            $('#editForm').submit(function (e) {
                e.preventDefault();
                $.post('CRUD.php?action=edit', $(this).serialize(), function (response) {
                    alert(response);
                    location.reload();
                });
            });

            // Handle Delete
            $('.deleteBtn').click(function () {
                if (confirm('Are you sure to delete this contact?')) {
                    $.post('CRUD.php?action=delete', { record_id: $(this).data('id') }, function (response) {
                        alert(response);
                        location.reload();
                    });
                }
            });

        });
    </script>
</body>
</html>