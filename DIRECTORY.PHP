<?php
    session_start();
    require 'db.php';
    require 'autolock.php';

    // ✅ Fetch all active users (optional: only show active)
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
    $role = isset($_GET['role']) && $_GET['role'] !== '' ? $_GET['role'] : null;
    $departmentFilter = isset($_GET['department']) && $_GET['department'] !== '' ? $_GET['department'] : null;

    // 🔧 Audit Trail Function
    function logAction($conn, $user_id, $action, $description = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

        $sql = "INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $action, $description, $ip, $agent);
        $stmt->execute();
    }

    // Define variables for audit logging
    $searchText = isset($_GET['search']) ? $_GET['search'] : '';
    $roleText = isset($_GET['role']) ? $_GET['role'] : 'All';
    $deptText = isset($_GET['department']) ? $_GET['department'] : 'All';

    $userId = $_SESSION['user_id'] ?? 0;

    $details  = "Search: {$searchText}; Role Filter: {$roleText}; Department Filter: {$deptText}";
    logAction($conn, $userId, "Directory Search", $details);

    // 🗃️ Get list of departments for dropdown
    $deptResult = $conn->query("SELECT DISTINCT department FROM work_details ORDER BY department ASC");
    $departments = [];
    while ($rowDept = $deptResult->fetch_assoc()) {
        if (!empty($rowDept['department'])) {
            $departments[] = $rowDept['department'];
        }
    }
    
    $sql = "SELECT u.id, u.name, u.address, u.contact, u.email, u.role, u.profile_pic,
                w.department, w.position
            FROM users u
            LEFT JOIN work_details w ON u.id = w.user_id
            WHERE u.status = 'active' AND u.name LIKE ?";

    $params = [$search];
    $types = "s";

    if ($role) {
        $sql .= " AND role = ?";
        $params[] = $role;
        $types .= "s";
    }

    if ($departmentFilter) {
        $sql .= " AND w.department = ?";
        $params[] = $departmentFilter;
        $types .= "s";
    }

    $sql .= " ORDER BY name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $userCount = $result->num_rows;

    $countQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
    $totalEmployees = $countQuery->fetch_assoc()['total'];

    // ATTENDANCE FECTHING DATA 
    $today = date('Y-m-d');
    $sql = "SELECT u.id, u.name, u.address, u.contact, u.email, u.role, u.profile_pic,
                a.login_time, a.logout_time, a.work_type
            FROM users u
            LEFT JOIN attendance_logs a 
                ON u.id = a.user_id AND a.log_date = ?
            WHERE u.status = 'active' AND u.name LIKE ?";

    $params = [$today, $search];
    $types = "ss";

    if ($role) {
        $sql .= " AND u.role = ?";
        $params[] = $role;
        $types .= "s";
    }

    $sql .= " ORDER BY u.name ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    // 🕒 Attendance fetching for today
    $today = date('Y-m-d');
    $sql2 = "SELECT u.id, u.name, u.address, u.contact, u.email, u.role, u.profile_pic,
                    w.department, w.position,
                    a.login_time, a.logout_time, a.work_type
            FROM users u
            LEFT JOIN work_details w ON u.id = w.user_id
            LEFT JOIN attendance_logs a ON u.id = a.user_id AND a.log_date = ?
            WHERE u.status = 'active' AND u.name LIKE ?";
    $params2 = [$today, $search];
    $types2 = "ss";

    if ($role) {
        $sql2 .= " AND u.role = ?";
        $params2[] = $role;
        $types2 .= "s";
    }

    if ($departmentFilter) {
        $sql2 .= " AND w.department = ?";
        $params2[] = $departmentFilter;
        $types2 .= "s";
    }

    $sql2 .= " ORDER BY u.name ASC";

    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param($types2, ...$params2);
    $stmt2->execute();
    $result = $stmt2->get_result();
?>

    <?php include __DIR__ . '/layout/HEADER'; ?>
    <?php include __DIR__ . '/layout/NAVIGATION'; ?>

    <style>
        body {
            background: #f8f9fa;
        }
        /* Sidebar + Layout */
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
        /* Directory cards */
        .directory-card {
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
            padding: 20px;
            height: 100%;
        }
        .directory-card:hover {
            transform: translateY(-3px);
        }
        .profile-pic {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ddd;
        }

        /* Scroll-to-Top Button */
        #scrollUpBtn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 99;
            border: none;
            outline: none;
            background-color: #0d6efd; /* Bootstrap primary */
            color: white;
            cursor: pointer;
            padding: 12px 16px;
            border-radius: 50%;
            font-size: 18px;
            box-shadow: 0px 3px 6px rgba(0,0,0,0.2);
            display: none; /* hidden by default */
            transition: opacity 0.3s ease;
        }
        #scrollUpBtn:hover {
            background-color: #0b5ed7;
        }
    </style>

    <div id="layoutSidenav_content">
        <main>
            <div class="container mt-3">
            <h3 class="mb-4 text-left">Company Directory</h3>
            <form method="GET" class="row g-2 mb-4 align-items-end">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search by name" 
                        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                </div>

                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" <?= (isset($_GET['role']) && $_GET['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="superadmin" <?= (isset($_GET['role']) && $_GET['role'] === 'superadmin') ? 'selected' : '' ?>>Superadmin</option>
                        <option value="user" <?= (isset($_GET['role']) && $_GET['role'] === 'user') ? 'selected' : '' ?>>User</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept) ?>" <?= ($departmentFilter === $dept) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
            
            <p class="text-muted">No. of Records Found: <strong><?= $userCount ?></strong></p>

            <div class="row g-4">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="card directory-card text-start p-4">
                            <img src="<?= !empty($row['profile_pic']) ? 'uploads/' . $row['profile_pic'] : 'uploads/default.png' ?>" 
                                class="profile-pic mx-auto" alt="Profile">
                            <h5><?= htmlspecialchars($row['name']) ?></h5>
                            <p class="text-muted mb-1"><?= htmlspecialchars($row['role']) ?></p>
                            <p class="mb-1">🏢 <strong><?= htmlspecialchars($row['department'] ?? 'N/A') ?></strong></p>
                            <p class="mb-2">💼 <?= htmlspecialchars($row['position'] ?? 'N/A') ?></p>
                            <p>
                                📧 
                                <span id="email-<?= $row['id'] ?>"><?= htmlspecialchars($row['email']) ?></span>
                                <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                        data-email="<?= htmlspecialchars($row['email']) ?>">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </p>
                            <script>
                                document.querySelectorAll('.copy-btn').forEach(button => {
                                    button.addEventListener('click', () => {
                                        const email = button.getAttribute('data-email');
                                        navigator.clipboard.writeText(email).then(() => {
                                            // Optional: show feedback
                                            button.innerHTML = '✔'; // check mark
                                            setTimeout(() => {
                                                button.innerHTML = '<i class="fa-regular fa-copy"></i>';
                                            }, 1000);
                                        }).catch(err => {
                                            alert('Failed to copy email');
                                        });
                                    });
                                });
                            </script>
                            <p>📞 <span id="contact-<?= $row['id'] ?>"><?= htmlspecialchars($row['contact']) ?></span>
                                <button class="btn btn-sm btn-outline-secondary copy-btn" 
                                        data-contact="<?= htmlspecialchars($row['contact']) ?>">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </p>
                            <script>
                                document.querySelectorAll('.copy-btn').forEach(button => {
                                    button.addEventListener('click', () => {
                                        const contact = button.getAttribute('data-contact');
                                        navigator.clipboard.writeText(contact).then(() => {
                                            // Optional: show feedback
                                            button.innerHTML = '✔'; // check mark
                                            setTimeout(() => {
                                                button.innerHTML = '<i class="fa-regular fa-copy"></i>';
                                            }, 1000);
                                        }).catch(err => {
                                            alert('Failed to copy contact');
                                        });
                                    });
                                });
                            </script>

                            <p>🏠 <small class="text-secondary"><?= htmlspecialchars($row['address']) ?></small></p>
                            <div class="dropdown position-absolute top-0 end-0 mt-2 me-2">
                                <a class="text-decoration-none small text-primary dropdown-toggle" 
                                href="#" id="attendanceDropdown<?= $row['id'] ?>" 
                                role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    [Attendance]
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="attendanceDropdown<?= $row['id'] ?>">
                                    <?php if ($row['login_time'] || $row['logout_time'] || $row['work_type']): ?>
                                        <li class="px-3 py-2 small">
                                            🟢 <strong>Login:</strong> <?= $row['login_time'] ? date("h:i A", strtotime($row['login_time'])) : 'N/A'; ?>
                                        </li>
                                        <li class="px-3 py-2 small">
                                            🔴 <strong>Logout:</strong> <?= $row['logout_time'] ? date("h:i A", strtotime($row['logout_time'])) : 'N/A'; ?>
                                        </li>
                                        <li class="px-3 py-2 small">
                                            🏷 <strong>Work Type:</strong> <?= $row['work_type'] ? ucfirst(str_replace('_',' ',$row['work_type'])) : 'N/A'; ?>
                                        </li>
                                    <?php else: ?>
                                        <li class="px-3 py-2 small text-muted">No attendance record today</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <button onclick="scrollToTop()" id="scrollUpBtn" title="Go to top">
                <i class="fas fa-arrow-up"></i>
            </button>
            <br>
            <?php include __DIR__ . '/layout/FOOTER'; ?>
        </main>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>
    
        <script>
            // Show the button when user scrolls down 100px
            window.onscroll = function() { toggleScrollButton(); };

            function toggleScrollButton() {
                const btn = document.getElementById("scrollUpBtn");
                if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                    btn.style.display = "block";
                } else {
                    btn.style.display = "none";
                }
            }

            // Smooth scroll to top
            function scrollToTop() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


      
