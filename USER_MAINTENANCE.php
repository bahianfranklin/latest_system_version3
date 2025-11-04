<?php
    require 'db.php';

    // Ensure $activeTab has a default to avoid undefined variable warnings
    $activeTab = $_GET['maintenanceTabs'] ?? 'user_management'; // default to branch

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
                <div class="tab-pane fade <?= $activeTab=='user_management' ? 'show active' : '' ?>" id="user_management">
                    <?php include 'USERS.php'; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='schedules' ? 'show active' : '' ?>" id="schedules">
                    <?php include 'SCHEDULES.php'; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='leave_credit' ? 'show active' : '' ?>" id="leave_credit">
                    <?php include 'LEAVE_CREDIT.php'; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='working_hours' ? 'show active' : '' ?>" id="working_hours">
                    <?php include 'WORKING_HOURS.php'; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='201_files' ? 'show active' : '' ?>" id="201_files">
                    <?php include '201_FILE.php'; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='salary_rate' ? 'show active' : '' ?>" id="salary_rate">
                    <?php include 'SALARY_RATE.PHP'; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='approver_maintenance' ? 'show active' : '' ?>" id="approver_maintenance">
                    <?php include 'APPROVER_MAINTENANCE.php'; ?>
                </div>
                <div class="tab-pane fade <?= $activeTab=='role_access_management' ? 'show active' : '' ?>" id="role_access_management">
                    <?php include 'ROLE_ACCESS_MANAGEMENT.php'; ?>
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
    });
</script>