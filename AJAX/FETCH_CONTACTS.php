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

if ($perPage === "all") {
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

<table class="table table-bordered">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Address</th>
            <th>Contact No</th>
            <th>Actions</th>
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
                        <button class="btn btn-primary btn-sm editBtn"
                            data-id="<?= $row['id'] ?>"
                            data-fullname="<?= htmlspecialchars($row['fullname']) ?>"
                            data-address="<?= htmlspecialchars($row['address']) ?>"
                            data-contact="<?= htmlspecialchars($row['contact_no']) ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal">Edit</button>

                        <button class="btn btn-danger btn-sm deleteBtn"
                            data-id="<?= $row['id'] ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5">No records found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<nav>
<ul class="pagination">
<?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
        <a href="#" class="page-link" data-page="<?= $i ?>"><?= $i ?></a>
    </li>
<?php endfor; ?>
</ul>
</nav>
