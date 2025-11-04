<?php
    ob_start();                // ✅ Start output buffering
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require 'db.php';
    // require 'auth_helper.php';

    // ✅ Assume logged-in user
    $user_id = $_SESSION['user_id'] ?? 1; // change if needed

    // ✅ Only allow superadmin
    // if ($_SESSION['role'] !== 'superadmin') {
    //     die("Access denied");
    // }

    // ✅ Fetch users
    $result = $conn->query("SELECT * FROM users") or die("Query Error: " . $conn->error);

    // ✅ Get search keyword
    $search = $_GET['search'] ?? '';

    // ✅ Get per-page limit from dropdown (default 5)
    $limit = $_GET['limit'] ?? 5;

    // ✅ Current page
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

    // ✅ Build query with search
    $where = "";
    if (!empty($search)) {
        $search = $conn->real_escape_string($search);
        $where = "WHERE 
                    name LIKE '%$search%' 
                    OR email LIKE '%$search%' 
                    OR username LIKE '%$search%' 
                    OR role LIKE '%$search%' 
                    OR status LIKE '%$search%'";
    }

    // ✅ Count total records
    $totalResult = $conn->query("SELECT COUNT(*) as cnt FROM users $where order by name ASC");
    $totalRecords = $totalResult->fetch_assoc()['cnt'] ?? 0;

    // ✅ Pagination setup
    if ($limit === "all") {
        $perPage = $totalRecords;  // show everything
    } else {
        $perPage = intval($limit);
    }
    $totalPages = ($perPage > 0) ? ceil($totalRecords / $perPage) : 1;
    $offset = ($page - 1) * $perPage;

    // // ✅ Fetch paginated records
    // $sql = "SELECT * FROM users $where LIMIT $offset, $perPage";
    // $result = $conn->query($sql);

    // ✅ Fetch paginated + sorted records
    $sql = "SELECT * FROM users $where ORDER BY name ASC LIMIT $offset, $perPage";
    $result = $conn->query($sql);

    
//     // Get user role
//     $roleQuery = $conn->prepare("SELECT role FROM users WHERE id = ?");
//     $roleQuery->bind_param("i", $user_id);
//     $roleQuery->execute();
//     $roleResult = $roleQuery->get_result()->fetch_assoc();
//     $role_id = $roleResult ? $roleResult['role'] : 0;

//     // Check if user can view this module
//     if (!hasAccess($role_id, 'USERS.php', 'view')) {
//         die("<div style='padding:20px;color:red;'>Access Denied: You cannot view this module.</div>");
// }
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>User Management</title>
        <!-- ✅ Bootstrap -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- ✅ FontAwesome Icons -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="container mt-4">

        <div class="d-flex justify-content-between align-items-center mb-1">
            <h5>User Management</h5>
            <a href="ADD_USER.php" class="btn btn-success btn-sm">
                <i class="fa fa-plus"></i> Add New Employee
            </a>
        </div>

        <!-- ✅ Full-width search bar -->
        <form method="get" class="mb-3">
            <div class="row g-2">
                <div class="col-md-10">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                        class="form-control" placeholder="Search name, username, role, status...">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100">
                        <!-- <i class="fa fa-search"></i>  -->
                        Search
                    </button>
                </div>
                <div class="col-md-1">
                    <a href="user_maintenance.php" class="btn btn-secondary w-100">
                        <!-- <i class="fa fa-rotate-left"></i>  -->
                        Reset
                    </a>
                </div>
            </div>
        </form>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Profile</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php 
                        $i = $offset + 1; // ✅ continue numbering across pages
                        while($row = $result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?= $i++ ?></td> <!-- ✅ show row number instead of database id -->
                        <td><img src="uploads/<?= $row['profile_pic'] ?: 'default.png' ?>" class="rounded-circle" width="40" height="40"></td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['email'] ?></td>
                        <td><?= $row['username'] ?></td>
                        <td><?= $row['role'] ?></td>
                        <td>
                            <span class="badge bg-<?= $row['status'] == 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="SHOW_USER.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info">
                                <i class="fa fa-eye"></i>
                            </a>
                            <a href="EDIT_USER.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fa fa-pen"></i>
                            </a>
                            <a href="delete_user.php?id=<?= $row['id'] ?>" 
                                onclick="return confirm('Are you sure you want to delete?\n\nName: <?= $row['name'] ?>\nEmail: <?= $row['email'] ?>\nUsername: <?= $row['username'] ?>');" 
                                class="btn btn-sm btn-danger">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                    <!-- ✅ Pagination controls -->
                    <nav>
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                    href="?page=<?= $page-1 ?>&limit=<?= $perPage ?>&search=<?= urlencode($search) ?>">
                                    Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" 
                                    href="?page=<?= $i ?>&limit=<?= $perPage ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" 
                                    href="?page=<?= $page+1 ?>&limit=<?= $perPage ?>&search=<?= urlencode($search) ?>">
                                    Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <!-- ✅ Page Dropdown Pagination -->
                    <form method="get" class="d-inline">
                        <input type="hidden" name="limit" value="<?= $perPage ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <label for="pageSelect">Page</label>
                        <select name="page" id="pageSelect" onchange="this.form.submit()">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <option value="<?= $i ?>" <?= ($i == $page) ? 'selected' : '' ?>>
                                    Page <?= $i ?> of <?= $totalPages ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </form>

                    <!-- ✅ Dropdown entries (10, 25, 50, 100, or all) -->
                    <form method="get" action="/phpfile/LATEST_SYSTEM/USER_MAINTENANCE.php" style="margin-bottom:10px;">
                        <input type="hidden" name="page" value="<?= $page ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <label>Show 
                            <select name="limit" onchange="this.form.submit()">
                                <option value="5" <?= ($limit == 5) ? 'selected' : '' ?>>5</option>
                                <option value="10" <?= ($limit == 10) ? 'selected' : '' ?>>10</option>
                                <option value="25" <?= ($limit == 25) ? 'selected' : '' ?>>25</option>
                                <option value="50" <?= ($limit == 50) ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= ($limit == 100) ? 'selected' : '' ?>>100</option>
                                <option value="all" <?= ($limit === 'all') ? 'selected' : '' ?>>Show All</option>
                            </select>
                            entries
                        </label>
                    </form>

                    <!-- ✅ Export file to CSV, Excel & PDF -->
                    <form method="get" action="export_user_list.php" class="d-inline">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <label>Export:
                            <select name="type" onchange="this.form.submit()" class="form-select d-inline w-auto">
                            <option value="">-- Select --</option>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF</option>
                            </select>
                        </label>
                    </form>
        </div>
        <br>
        <!-- ✅ Bootstrap JS (for dropdowns, modals, etc.) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- 
        ?php if (hasAccess($role_id, 'USERS.php', 'add')): ?>
            <a href="add_user.php" class="btn btn-primary">Add User</a>
        ?php endif; ?>

        ?php if (hasAccess($role_id, 'USERS.php', 'edit')): ?>
            <a href="edit_user.php?id=?= $user['id'] ?>" class="btn btn-warning">Edit</a>
        ?php endif; ?>

        ?php if (hasAccess($role_id, 'USERS.php', 'delete')): ?>
            <a href="delete_user.php?id=?= $user['id'] ?>" class="btn btn-danger">Delete</a>
        ?php endif; ?> -->


    </body>
</html>
