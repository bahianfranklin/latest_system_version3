<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/autolock.php';
require_once __DIR__ . '/audit.php';

/* 🔐 RBAC GUARD */
if (!canView('log_history')) {
    http_response_code(403);
    exit('Access Denied');
}

if (!isset($_SESSION['user'])) {
    header("Location: LOGIN.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];  // ✅ define user_id

// Get search keyword and date range from GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$from = isset($_GET['from']) ? trim($_GET['from']) : '';
$to = isset($_GET['to']) ? trim($_GET['to']) : '';

// --- AUDIT TRAIL FOR SEARCH ---
if (isset($_GET['search']) || isset($_GET['from']) || isset($_GET['to'])) {

    $desc = "Search filters used: ";

    if ($search !== '') {
        $desc .= "Keyword = '$search'; ";
    }
    if ($from !== '') {
        $desc .= "From = $from; ";
    }
    if ($to !== '') {
        $desc .= "To = $to; ";
    }

    logAction($conn, $user_id, "Log History Search", $desc);
}

// Base SQL
$sql = "SELECT l.id, u.name AS fullname, u.username, l.login_time, l.logout_time, l.ip_address
            FROM user_logs l
            JOIN users u ON l.user_id = u.id";

// Prepare conditions
$conditions = [];
$params = [];
$types = '';

// ✅ Add user restriction first
$conditions[] = "l.user_id = ?";
$params[] = $user_id;
$types .= 'i';

// Add search condition
if ($search !== '') {
    $conditions[] = "(u.name LIKE ? OR u.username LIKE ? OR l.ip_address LIKE ?)";
    $likeSearch = "%$search%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= 'sss';
}

// Add date range conditions
if ($from !== '') {
    $conditions[] = "l.login_time >= ?";
    $params[] = $from . " 00:00:00";
    $types .= 's';
}
if ($to !== '') {
    $conditions[] = "l.login_time <= ?";
    $params[] = $to . " 23:59:59";
    $types .= 's';
}

// Combine conditions
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY l.login_time DESC";

// Prepare statement
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// --- PAGINATION SETUP ---
// Get limit per page (default 10)
$perPage = isset($_GET['limit']) ? $_GET['limit'] : 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Clone SQL for counting total records
$countSql = "SELECT COUNT(*) as total FROM user_logs l JOIN users u ON l.user_id = u.id";
if (!empty($conditions)) {
    $countSql .= " WHERE " . implode(" AND ", $conditions);
}

$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];

// Compute total pages
if ($perPage === "all") {
    $totalPages = 1;
    $offset = 0;
} else {
    $perPage = intval($perPage);
    $totalPages = ceil($totalRecords / $perPage);
    $offset = ($page - 1) * $perPage;
}

// Final SQL with LIMIT
$sql .= ($perPage === "all") ? "" : " LIMIT $offset, $perPage";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

//Pagination logic for blocks of 5 pages
// Number of pages per block
$pagesToShow = 5;

// Determine which block we are in
$currentBlock = ceil($page / $pagesToShow);

// Calculate block start & end
$startPage = ($currentBlock - 1) * $pagesToShow + 1;
$endPage = min($startPage + $pagesToShow - 1, $totalPages);

// Preserve filters
$baseParams = [
    'limit' => $perPage,
    'search' => $search,
    'from' => $from,
    'to' => $to,
];

// Build URL helper
$pageUrl = function ($p) use ($baseParams) {
    $q = $baseParams;
    $q['page'] = $p;
    return '?' . http_build_query($q);
};
?>


<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<!-- Bootstrap & Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

<!-- Custom Styles -->
<link href="css/styles.css" rel="stylesheet" />

<!-- Sidebar Toggle CSS -->
<style>
    #layoutSidenav {
        display: flex;
    }

    #layoutSidenav_nav {
        width: 250px;
        flex-shrink: 0;
        transition: margin-left 0.3s ease;
    }

    #layoutSidenav_content {
        flex-grow: 1;
        min-width: 0;
        transition: margin-left 0.3s ease;
    }
</style>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <br>
            <h4>Login / Logout History</h4>
            <br>
            <!-- Search form with From/To dates -->
            <form method="get" class="mb-3 row g-2 align-items-end">
                <div class="col-md-3">
                    <label><strong><em>Keyword</em></strong></label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, username, or IP"
                        value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <label><strong><em>From:</em></strong></label>
                    <input type="date" name="from" class="form-control" value="<?= htmlspecialchars($from) ?>">
                </div>
                <div class="col-md-3">
                    <label><strong><em>To:</em></strong></label>
                    <input type="date" name="to" class="form-control" value="<?= htmlspecialchars($to) ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="?" class="btn btn-secondary">Reset</a>
                </div>
            </form>

            <table class="table table-bordered table-striped mt-3">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>FULLNAME</th>
                        <th>Username</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>IP Address</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $counter = 1;
                    while ($row = $result->fetch_assoc()):
                        $login_time = date("Y-m-d h:i:s A", strtotime($row['login_time']));
                        $logout_time = $row['logout_time'] ? date("Y-m-d h:i:s A", strtotime($row['logout_time'])) : '---';
                        ?>
                        <tr>
                            <td><?= $offset + $counter ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= $login_time ?></td>
                            <td><?= $logout_time ?></td>
                            <td><?= htmlspecialchars($row['ip_address']) ?></td>
                        </tr>
                        <?php
                        $counter++;
                    endwhile;
                    ?>
                </tbody>
            </table>
            <br>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                <!-- Pagination -->
                <nav>
                    <ul class="pagination mb-0">
                        <!-- Previous -->
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= ($page > 1) ? $pageUrl($page - 1) : '#' ?>">Previous</a>
                        </li>

                        <!-- Page Numbers (block of 5) -->
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $pageUrl($i) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next -->
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link"
                                href="<?= ($page < $totalPages) ? $pageUrl($page + 1) : '#' ?>">Next</a>
                        </li>
                    </ul>
                </nav>

                <!-- Page Dropdown -->
                <form method="get" class="d-inline ms-3">
                    <input type="hidden" name="limit" value="<?= $perPage ?>">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
                    <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">

                    <label for="pageSelect">Page</label>
                    <select name="page" id="pageSelect" onchange="this.form.submit()">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <option value="<?= $i ?>" <?= ($i == $page) ? 'selected' : '' ?>>
                                <?= "Page $i of $totalPages" ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </form>

                <!-- Dropdown for limit -->
                <form method="get" class="d-flex align-items-center ms-2">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
                    <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">
                    <input type="hidden" name="page" value="1">
                    <label class="me-2">Show</label>
                    <select name="limit" class="form-select w-auto" onchange="this.form.submit()">
                        <option value="5" <?= ($perPage == 5) ? 'selected' : '' ?>>5</option>
                        <option value="10" <?= ($perPage == 10) ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= ($perPage == 25) ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= ($perPage == 50) ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= ($perPage == 100) ? 'selected' : '' ?>>100</option>
                        <option value="all" <?= ($perPage === 'all') ? 'selected' : '' ?>>Show All</option>
                    </select>
                    <label class="ms-2">entries</label>
                </form>

                <!-- Export Dropdown -->
                <form method="get" action="export_log_history.php" class="d-inline">
                    <label>Export:
                        <select id="exportSelect" class="form-select d-inline-block w-auto"
                            onchange="if(this.value) window.location.href=this.value;">
                            <option value="">-- Select --</option>
                            <option value="export_log_history.php?type=csv">CSV</option>
                            <option value="export_log_history.php?type=excel">Excel</option>
                            <option value="export_log_history.php?type=pdf">PDF</option>
                        </select>
                    </label>
                </form>
            </div>
    </main>
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