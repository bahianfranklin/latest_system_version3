<?php
require_once __DIR__ . "/CONFIG_2.php";
require_once __DIR__ . "/WEBLIB.php";

$weblib = new WebLib();

$search = $_GET['search'] ?? '';
$perPage = $_GET['limit'] ?? 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

$url = "https://api.mandbox.com/apitest/v1/contact.php?action=view";
$params = ["record_id" => ""];

$weblib->requestURL($url, $params);
$dataList = $weblib->resultData() ?? [];

$records = json_decode(json_encode($dataList), true);

// SEARCH
if (!empty($search)) {
    $records = array_filter($records, function ($row) use ($search) {
        return stripos($row['fullname'] ?? '', $search) !== false ||
            stripos($row['address'] ?? '', $search) !== false ||
            stripos($row['contact_no'] ?? '', $search) !== false;
    });
    $records = array_values($records);
}

$totalRecords = count($records);

if ($perPage === "all") { // Show all records
    $currentRecords = $records;
    $totalPages = 1;
    $offset = 0;
} else {
    $perPage = intval($perPage);
    $totalPages = ceil($totalRecords / $perPage);
    $offset = ($page - 1) * $perPage;
    $currentRecords = array_slice($records, $offset, $perPage);
}
?>

<table class="table table-bordered table-hover align-middle">
    <thead class="table-dark">
        <tr>
            <td><?= $i++ ?></td>
            <td class="col-1"><?= htmlspecialchars($row['fullname']) ?></td>
            <td class="col-2"><?= htmlspecialchars($row['address']) ?></td>
            <td class="col-3"><?= htmlspecialchars($row['contact_no']) ?></td>
            <td class="col-4">Actions</td>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($currentRecords)): ?>
            <?php $i = $offset + 1; ?>
            <?php foreach ($currentRecords as $row): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td><?= htmlspecialchars($row['contact_no']) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm editBtn" data-id="<?= $row['id'] ?>"
                            data-fullname="<?= htmlspecialchars($row['fullname']) ?>"
                            data-address="<?= htmlspecialchars($row['address']) ?>"
                            data-contact="<?= htmlspecialchars($row['contact_no']) ?>" data-bs-toggle="modal"
                            data-bs-target="#editModal">Edit</button>

                        <button class="btn btn-danger btn-sm deleteBtn" data-id="<?= $row['id'] ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No records found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php
$start = ($totalRecords > 0) ? $offset + 1 : 0;
$end = $offset + count($currentRecords);
?>

<!-- Bottom Controls -->
<div class="row align-items-center mt-3">

    <!-- Show Entries -->
    <div class="col-md-4 mb-2 mb-md-0">
        <div class="d-flex align-items-center">
            <span class="me-2">Show</span>
            <select id="limit" class="form-select form-select-sm w-auto">
                <option value="5" <?= ($perPage == 5) ? 'selected' : '' ?>>5</option>
                <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10</option>
                <option value="25" <?= ($perPage == 25) ? 'selected' : '' ?>>25</option>
                <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50</option>
                <option value="all" <?= ($perPage === "all") ? 'selected' : '' ?>>All</option>
            </select>
            <span class="ms-2">entries</span>
        </div>
    </div>

    <!-- Showing Info -->
    <div class="col-md-4 text-center mb-2 mb-md-0">
        <small>
            Showing <?= $start ?> to <?= $end ?> of <?= $totalRecords ?> records
        </small>
    </div>

    <!-- Pagination -->
    <!-- <div class="col-md-4 text-md-end">
        ?php if ($totalPages > 1 && $perPage !== "search"): ?>
            <ul class="pagination pagination-sm justify-content-md-end justify-content-center mb-0">

                <li class="page-item ?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a href="#" class="page-link" data-page="?= $page - 1 ?>">Prev</a>
                </li>

                <li class="page-item disabled">
                    <span class="page-link">
                        Page ?= $page ?> of ?= $totalPages ?>
                    </span>
                </li>

                <li class="page-item ?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a href="#" class="page-link" data-page="?= $page + 1 ?>">Next</a>
                </li>

            </ul>
        ?php endif; ?>
    </div> -->

    <div class="col-md-4 text-md-end">
        <?php if ($totalPages > 1 && $perPage !== "search"): ?>
            <ul class="pagination pagination-sm justify-content-md-end justify-content-center mb-0">

                <!-- Prev button -->
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a href="#" class="page-link" data-page="<?= $page - 1 ?>">Prev</a>
                </li>

                <?php
                // Limit to 5 visible page buttons
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);

                if ($end - $start < 4) {
                    if ($start == 1) {
                        $end = min($start + 4, $totalPages);
                    } else {
                        $start = max($end - 4, 1);
                    }
                }

                for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a href="#" class="page-link" data-page="<?= $i ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <!-- Next button -->
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a href="#" class="page-link" data-page="<?= $page + 1 ?>">Next</a>
                </li>

            </ul>
        <?php endif; ?>
    </div>

</div>