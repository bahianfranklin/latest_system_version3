<?php
    require 'db.php';

    // Ensure $activeTab has a default to avoid undefined variable warnings
    $activeTab = $_GET['maintenanceTabs'] ?? 'user_management';

    // --- HANDLE SCHEDULES POST AND DELETE ---
    // EDIT or INSERT
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user_id'])) {
        $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        $values = [];
        foreach ($days as $day) {
            $values[$day] = $_POST[$day] ?? 'rest_day';
        }

        $user_id = $_POST['user_id'];

        // Check if schedule exists
        $stmt_check = $conn->prepare("SELECT id FROM employee_schedules WHERE user_id=?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if($row_check = $result_check->fetch_assoc()) {
            // UPDATE
            $schedule_id = $row_check['id'];
            $stmt = $conn->prepare("UPDATE employee_schedules 
                SET monday=?, tuesday=?, wednesday=?, thursday=?, friday=?, saturday=?, sunday=? 
                WHERE id=?");
            $stmt->bind_param(
                "sssssssi",
                $values['monday'], $values['tuesday'], $values['wednesday'],
                $values['thursday'], $values['friday'], $values['saturday'], $values['sunday'],
                $schedule_id
            );
            $stmt->execute();
            $stmt->close();
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO employee_schedules 
                (user_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "isssssss",
                $user_id,
                $values['monday'], $values['tuesday'], $values['wednesday'],
                $values['thursday'], $values['friday'], $values['saturday'], $values['sunday']
            );
            $stmt->execute();
            $stmt->close();
        }

        // Redirect back to schedules tab
        header("Location: USER_MAINTENANCE.php?maintenanceTabs=schedules");
        exit;
    }

    // DELETE
    if (isset($_GET['delete_id']) && !empty($_GET['delete_id'])) {
        $id = $_GET['delete_id'];
        $stmt = $conn->prepare("DELETE FROM employee_schedules WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        header("Location: USER_MAINTENANCE.php?maintenanceTabs=schedules");
        exit;
    }

    include __DIR__ . '/layout/HEADER';
    include __DIR__ . '/layout/NAVIGATION';
?>

<div id="layoutSidenav_content">
    <main>
        <div class="container mt-3">
            </br>
            <h3 class="m-0">User Maintenance</h3>
            <br>
            <ul class="nav nav-tabs" id="maintenanceTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab=='user_management' ? 'active' : '' ?>" data-bs-toggle="tab" href="#user_management">User Management</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab=='schedules' ? 'active' : '' ?>" data-bs-toggle="tab" href="#schedules">Schedules</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab=='leave_credit' ? 'active' : '' ?>" data-bs-toggle="tab" href="#leave_credit">Leave Credit</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab=='working_hours' ? 'active' : '' ?>" data-bs-toggle="tab" href="#working_hours">Working Hours</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab=='201_files' ? 'active' : '' ?>" data-bs-toggle="tab" href="#201_files">201 Files</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab=='salary_rate' ? 'active' : '' ?>" data-bs-toggle="tab" href="#salary_rate">HR Salary Mgnt</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab=='approver_maintenance' ? 'active' : '' ?>" data-bs-toggle="tab" href="#approver_maintenance">Approvers Maintenance</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab=='role_access_management' ? 'active' : '' ?>" data-bs-toggle="tab" href="#role_access_management">Role Access Mngt</a>
                </li>
            </ul>

            <div class="tab-content mt-3">
                <div class="tab-pane fade <?= $activeTab=='user_management' ? 'show active' : '' ?>" id="user_management" data-tab="USERS.php">
                    <?php if($activeTab=='user_management'): include 'USERS.php'; endif; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='schedules' ? 'show active' : '' ?>" id="schedules" data-tab="SCHEDULES.php">
                    <?php if($activeTab=='schedules'): include 'SCHEDULES.php'; endif; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='leave_credit' ? 'show active' : '' ?>" id="leave_credit" data-tab="LEAVE_CREDIT.php">
                    <?php if($activeTab=='leave_credit'): include 'LEAVE_CREDIT.php'; endif; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='working_hours' ? 'show active' : '' ?>" id="working_hours" data-tab="WORKING_HOURS.php">
                    <?php if($activeTab=='working_hours'): include 'WORKING_HOURS.php'; endif; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='201_files' ? 'show active' : '' ?>" id="201_files" data-tab="201_FILE.php">
                    <?php if($activeTab=='201_files'): include '201_FILE.php'; endif; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='salary_rate' ? 'show active' : '' ?>" id="salary_rate" data-tab="SALARY_RATE.PHP">
                    <?php if($activeTab=='salary_rate'): include 'SALARY_RATE.PHP'; endif; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='approver_maintenance' ? 'show active' : '' ?>" id="approver_maintenance" data-tab="APPROVER_MAINTENANCE.php">
                    <?php if($activeTab=='approver_maintenance'): include 'APPROVER_MAINTENANCE.php'; endif; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='role_access_management' ? 'show active' : '' ?>" id="role_access_management" data-tab="ROLE_ACCESS_MANAGEMENT.php">
                    <?php if($activeTab=='role_access_management'): include 'ROLE_ACCESS_MANAGEMENT.php'; endif; ?>
                </div>
            </div>           
        </div>
    </main>
    <?php include __DIR__ . '/layout/FOOTER'; ?>
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

        // Lazy load tabs on click
        const tabLinks = document.querySelectorAll('#maintenanceTabs a[data-bs-toggle="tab"]');
        tabLinks.forEach(link => {
            link.addEventListener('shown.bs.tab', function(e) {
                const targetId = e.target.getAttribute('href').substring(1);
                const tabPane = document.getElementById(targetId);
                const tabFile = tabPane.getAttribute('data-tab');

                // Only load if tab is empty
                if (tabPane.innerHTML.trim() === '') {
                    fetch('LOAD_TAB.php?tab=' + encodeURIComponent(tabFile))
                        .then(response => response.text())
                        .then(html => {
                            tabPane.innerHTML = html;
                        })
                        .catch(error => console.error('Error loading tab:', error));
                }
            });
        });
    });
</script>