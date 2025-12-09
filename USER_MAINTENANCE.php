<?php
    require 'db.php';
    require 'autolock.php';

    // Ensure $activeTab has a default to avoid undefined variable warnings
    // Accept both new ?maintenanceTabs= and legacy ?tab= for backward compatibility
    $activeTab = $_GET['maintenanceTabs'] ?? $_GET['tab'] ?? 'user_management';

    // --- HANDLE SCHEDULES POST AND DELETE ---
    // Only process when the schedules form was submitted (uses hidden field 'schedule_submit')
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['schedule_submit'])) {
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

    // --- HANDLE ROLE ACCESS MANAGEMENT ACTIONS ---
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['role_access_action']) && isset($_POST['module'])) {
        $role_id = intval($_POST['role_id'] ?? 0);

        if ($role_id > 0) {
            // Delete old access for this role
            $stmtDel = $conn->prepare("DELETE FROM role_access WHERE role_id = ?");
            if ($stmtDel) {
                $stmtDel->bind_param("i", $role_id);
                if (!$stmtDel->execute()) {
                    error_log('USER_MAINTENANCE role_access: delete failed: ' . $conn->error);
                }
                $stmtDel->close();
            }

            // Insert new access settings
            foreach ($_POST['module'] as $module_id => $actions) {
                $module_id = intval($module_id);
                $can_view   = isset($actions['view']) ? 1 : 0;
                $can_add    = isset($actions['add']) ? 1 : 0;
                $can_edit   = isset($actions['edit']) ? 1 : 0;
                $can_delete = isset($actions['delete']) ? 1 : 0;

                // Only insert if at least one permission is granted
                if ($can_view || $can_add || $can_edit || $can_delete) {
                    $stmt = $conn->prepare("
                        INSERT INTO role_access (role_id, module_id, can_view, can_add, can_edit, can_delete)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    if ($stmt) {
                        $stmt->bind_param("iiiiii", $role_id, $module_id, $can_view, $can_add, $can_edit, $can_delete);
                        if (!$stmt->execute()) {
                            error_log('USER_MAINTENANCE role_access: execute failed for module_id=' . $module_id . ': ' . $conn->error);
                        }
                        $stmt->close();
                    } else {
                        error_log('USER_MAINTENANCE role_access: prepare failed: ' . $conn->error);
                    }
                }
            }
        }

        header("Location: USER_MAINTENANCE.php?maintenanceTabs=role_access_management&role_id=" . $role_id . "&success=1");
        exit();
    }

    // --- HANDLE APPROVER MAINTENANCE ACTIONS ---
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['approver_action'])) {
        $action = $_POST['action'] ?? null;

        if ($action === "insert") {
            $work_detail_id = (int) ($_POST['work_detail_id'] ?? 0);
            $department_id  = (int) ($_POST['department_id'] ?? 0);

            if ($work_detail_id > 0 && $department_id > 0) {
                // Check if department already has an approver
                $check = $conn->prepare("SELECT COUNT(*) FROM approver_assignments WHERE department_id = ?");
                if ($check) {
                    $check->bind_param("i", $department_id);
                    $check->execute();
                    $check->bind_result($count);
                    $check->fetch();
                    $check->close();

                    if ($count > 0) {
                        header("Location: USER_MAINTENANCE.php?maintenanceTabs=approver_maintenance&error=department_exists");
                        exit();
                    }
                }

                // Get user_id from work_details
                $stmtUser = $conn->prepare("SELECT user_id FROM work_details WHERE work_detail_id = ?");
                if ($stmtUser) {
                    $stmtUser->bind_param("i", $work_detail_id);
                    $stmtUser->execute();
                    $stmtUser->bind_result($user_id);
                    $stmtUser->fetch();
                    $stmtUser->close();

                    if ($user_id) {
                        $stmt = $conn->prepare("INSERT INTO approver_assignments (user_id, work_detail_id, department_id) VALUES (?, ?, ?)");
                        if ($stmt) {
                            $stmt->bind_param("iii", $user_id, $work_detail_id, $department_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
            header("Location: USER_MAINTENANCE.php?maintenanceTabs=approver_maintenance&success=1");
            exit();
        }

        if ($action === "update") {
            $id             = (int) ($_POST['id'] ?? 0);
            $work_detail_id = (int) ($_POST['work_detail_id'] ?? 0);
            $department_id  = (int) ($_POST['department_id'] ?? 0);

            if ($id > 0 && $work_detail_id > 0 && $department_id > 0) {
                $stmtUser = $conn->prepare("SELECT user_id FROM work_details WHERE work_detail_id = ?");
                if ($stmtUser) {
                    $stmtUser->bind_param("i", $work_detail_id);
                    $stmtUser->execute();
                    $stmtUser->bind_result($user_id);
                    $stmtUser->fetch();
                    $stmtUser->close();

                    if ($user_id) {
                        $stmt = $conn->prepare("UPDATE approver_assignments SET user_id=?, work_detail_id=?, department_id=? WHERE id=?");
                        if ($stmt) {
                            $stmt->bind_param("iiii", $user_id, $work_detail_id, $department_id, $id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
            header("Location: USER_MAINTENANCE.php?maintenanceTabs=approver_maintenance&success=1");
            exit();
        }

        if ($action === "delete") {
            $id = (int) ($_POST['id'] ?? 0);

            if ($id > 0) {
                $stmt = $conn->prepare("DELETE FROM approver_assignments WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            header("Location: USER_MAINTENANCE.php?maintenanceTabs=approver_maintenance&success=1");
            exit();
        }
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

        // Delegated modal handlers so modals loaded via AJAX still get their events
        document.addEventListener('show.bs.modal', function (event) {
            var modal = event.target;
            var button = event.relatedTarget;
            if (!button) return;

            // Leave Credit edit modal
            if (modal.id === 'editModal') {
                var userIdField = document.getElementById('modalUserId');
                var mandatoryField = document.getElementById('modalMandatory');
                var vacationField = document.getElementById('modalVacation');
                var sickField = document.getElementById('modalSick');
                if (userIdField) userIdField.value = button.getAttribute('data-id') || '';
                if (mandatoryField) mandatoryField.value = button.getAttribute('data-mandatory') || 0;
                if (vacationField) vacationField.value = button.getAttribute('data-vacation') || 0;
                if (sickField) sickField.value = button.getAttribute('data-sick') || 0;
            }

            // Leave Credit delete modal
            if (modal.id === 'deleteModal') {
                var deleteUserId = document.getElementById('deleteUserId');
                var deleteUserName = document.getElementById('deleteUserName');
                if (deleteUserId) deleteUserId.value = button.getAttribute('data-id') || '';
                if (deleteUserName) deleteUserName.textContent = button.getAttribute('data-name') || '';
            }
        });

        // Delegated handlers for Working Hours (buttons and forms inside AJAX-loaded tab)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.editBtn, .deleteBtn, .addBtn');
            if (!btn) return;

            // Edit working hours
            if (btn.classList.contains('editBtn')) {
                var id = btn.getAttribute('data-id');
                var day = btn.getAttribute('data-day');
                var timein = btn.getAttribute('data-timein');
                var timeout = btn.getAttribute('data-timeout');
                var modal = document.getElementById('editModal');
                if (modal) {
                    var elId = modal.querySelector('#edit_id'); if (elId) elId.value = id || '';
                    var elDay = modal.querySelector('#edit_day'); if (elDay) elDay.value = day || '';
                    var elIn = modal.querySelector('#edit_time_in'); if (elIn) elIn.value = timein || '';
                    var elOut = modal.querySelector('#edit_time_out'); if (elOut) elOut.value = timeout || '';
                    new bootstrap.Modal(modal).show();
                }
            }

            // Delete working hours
            if (btn.classList.contains('deleteBtn')) {
                var id = btn.getAttribute('data-id');
                var modal = document.getElementById('deleteModal');
                if (modal) {
                    var el = modal.querySelector('#delete_id'); if (el) el.value = id || '';
                    new bootstrap.Modal(modal).show();
                }
            }

            // Add working hours
            if (btn.classList.contains('addBtn')) {
                var userid = btn.getAttribute('data-userid');
                var day = btn.getAttribute('data-day');
                var modal = document.getElementById('addModal');
                if (modal) {
                    var elUser = modal.querySelector('#add_user_id'); if (elUser) elUser.value = userid || '';
                    var elWorkDay = modal.querySelector('#add_work_day'); if (elWorkDay) elWorkDay.value = day || '';
                    var elDisplay = modal.querySelector('#add_day_display'); if (elDisplay) elDisplay.value = day || '';
                    new bootstrap.Modal(modal).show();
                }
            }
        });

        // Delegated handler for 201 File upload buttons (works when 201_FILE.php is lazy-loaded)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.upload-btn');
            if (!btn) return;
            var userid = btn.getAttribute('data-userid');
            var reqid = btn.getAttribute('data-reqid') || '';
            var filename = btn.getAttribute('data-filename') || '';
            var modalUser = document.getElementById('modal_user_id');
            var modalReq = document.getElementById('modal_requirement_id');
            var currentFile = document.getElementById('current_file');
            if (modalUser) modalUser.value = userid || '';
            if (modalReq) modalReq.value = reqid || '';
            if (currentFile) currentFile.textContent = filename ? 'Current file: ' + filename : '';
            var modal = document.getElementById('uploadModal');
            if (modal) new bootstrap.Modal(modal).show();
        });

        // Delegated submit handlers for working hours forms (AJAX)
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form) return;

            // Edit form
            if (form.id === 'editForm') {
                e.preventDefault();
                var data = new FormData(form);
                fetch('UPDATE_WORKING_HOURS.php', { method: 'POST', body: data })
                    .then(r => r.text())
                    .then(text => { alert(text); location.reload(); })
                    .catch(err => console.error(err));
            }

            // Delete form
            if (form.id === 'deleteForm') {
                e.preventDefault();
                var data = new FormData(form);
                fetch('DELETE_WORKING_HOURS.php', { method: 'POST', body: data })
                    .then(r => r.text())
                    .then(text => { alert(text); location.reload(); })
                    .catch(err => console.error(err));
            }

            // Add form
            if (form.id === 'addForm') {
                e.preventDefault();
                var data = new FormData(form);
                fetch('ADD_WORKING_HOURS.php', { method: 'POST', body: data })
                    .then(r => r.text())
                    .then(text => { alert(text); location.reload(); })
                    .catch(err => console.error(err));
            }
        });
    });
</script>