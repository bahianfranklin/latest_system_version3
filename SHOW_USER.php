<?php
    session_start();
    require 'db.php';

    // ✅ Only allow logged in users
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // ✅ Get user ID from URL
    $id = $_GET['id'] ?? null;
    if (!$id) {
        die("Invalid request. User ID is required.");
    }

    // ✅ Fetch user details with work_details
    $stmt = $conn->prepare("SELECT u.*, w.work_detail_id, w.employee_no, w.bank_account_no, w.sss_no, 
                                w.philhealth_no, w.pagibig_no, w.tin_no, w.date_hired, w.regularization, 
                                w.branch, w.department, w.position, w.level_desc, w.tax_category, 
                                w.status_desc, w.leave_rule, w.created_at
                            FROM users u
                            LEFT JOIN work_details w ON u.id = w.user_id
                            WHERE u.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        die("User not found.");
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Show User</title>
        <!-- ✅ Bootstrap -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- ✅ FontAwesome Icons -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="container mt-4">

    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">User Details</h3>
        </div>
        <div class="card-body">
            <div class="text-center mb-3">
                <img src="uploads/<?= $user['profile_pic'] ?: 'default.png' ?>" 
                    class="rounded-circle border shadow-sm" 
                    width="120" height="120">
            </div>
            <h5 class="mb-3 text-primary"><i class="fa fa-user"></i> Basic Information</h5>
            <table class="table table-bordered">
                <tr><th style="width: 200px;">Name</th><td><?= htmlspecialchars($user['name']) ?></td></tr>
                <tr><th>Email</th><td><?= htmlspecialchars($user['email']) ?></td></tr>
                <tr><th>Username</th><td><?= htmlspecialchars($user['username']) ?></td></tr>
                <tr><th>Role</th><td><?= htmlspecialchars($user['role']) ?></td></tr>
                <tr><th>Status</th>
                    <td>
                        <span class="badge bg-<?= $user['status'] == 'active' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($user['status']) ?>
                        </span>
                    </td>
                </tr>
            </table>

            <h5 class="mt-4 mb-3 text-primary"><i class="fa fa-briefcase"></i> Work Details</h5>
            <?php if ($user['work_detail_id']): ?>
                <table class="table table-bordered">
                    <tr><th>Employee No</th><td><?= htmlspecialchars($user['employee_no']) ?></td></tr>
                    <tr><th>Bank Account No</th><td><?= htmlspecialchars($user['bank_account_no']) ?></td></tr>
                    <tr><th>SSS No</th><td><?= htmlspecialchars($user['sss_no']) ?></td></tr>
                    <tr><th>PhilHealth No</th><td><?= htmlspecialchars($user['philhealth_no']) ?></td></tr>
                    <tr><th>Pag-IBIG No</th><td><?= htmlspecialchars($user['pagibig_no']) ?></td></tr>
                    <tr><th>TIN No</th><td><?= htmlspecialchars($user['tin_no']) ?></td></tr>
                    <tr><th>Date Hired</th><td><?= htmlspecialchars($user['date_hired']) ?></td></tr>
                    <tr><th>Regularization</th><td><?= htmlspecialchars($user['regularization']) ?></td></tr>
                    <tr><th>Branch</th><td><?= htmlspecialchars($user['branch']) ?></td></tr>
                    <tr><th>Department</th><td><?= htmlspecialchars($user['department']) ?></td></tr>
                    <tr><th>Position</th><td><?= htmlspecialchars($user['position']) ?></td></tr>
                    <tr><th>Level</th><td><?= htmlspecialchars($user['level_desc']) ?></td></tr>
                    <tr><th>Tax Category</th><td><?= htmlspecialchars($user['tax_category']) ?></td></tr>
                    <tr><th>Status (Work)</th><td><?= htmlspecialchars($user['status_desc']) ?></td></tr>
                    <tr><th>Leave Rule</th><td><?= htmlspecialchars($user['leave_rule']) ?></td></tr>
                    <tr><th>Created At</th><td><?= htmlspecialchars($user['created_at']) ?></td></tr>
                </table>
            <?php else: ?>
                <div class="alert alert-warning">No work details found for this user.</div>
            <?php endif;?>
        </div>

        <div class="card-footer text-end print-hide">
            <button onclick="window.print()" class="btn btn-primary me-2">
             <i class="fa fa-print"></i> Print
            </button>
            <a href="USER_MAINTENANCE.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <style>
    @media print {
        .print-hide, .btn, a, .card-footer {
            display: none !important;
        }

        body {
            background: white !important;
            color: black;
        }

        .card {
            box-shadow: none !important;
            border: none !important;
        }

        img {
            max-width: 120px;
            max-height: 120px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        h3, h5 {
            color: black !important;
        }
    }
    </style>

    <script>
    //   window.print(); // Uncomment if you want it to print immediately
    </script>

    <!-- ✅ Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
