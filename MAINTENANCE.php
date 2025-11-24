<?php 
    require 'db.php';
    require 'autolock.php';

    ob_start();                // ✅ Start output buffering
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $activeTab = $_GET['tab'] ?? 'branch'; // default to branch

    if (!isset($_SESSION['user'])) {
        header("Location: LOGIN.php");
        exit();
    }

    $user = $_SESSION['user'];

    include __DIR__ . '/layout/HEADER';
    include __DIR__ . '/layout/NAVIGATION';
?>

    <div id="layoutSidenav_content">
        <main>
            <div class="container-fluid px-4">
                </br>
                <h3 class="m-0">Maintenance Tabs</h3>
                <br>
                <ul class="nav nav-tabs" id="maintenanceTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='branch' ? 'active' : '' ?>" data-bs-toggle="tab" href="#branch">Branch</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='department' ? 'active' : '' ?>" data-bs-toggle="tab" href="#department">Departments</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='position' ? 'active' : '' ?>" data-bs-toggle="tab" href="#position">Position</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='level' ? 'active' : '' ?>" data-bs-toggle="tab" href="#level">Level</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='tax' ? 'active' : '' ?>" data-bs-toggle="tab" href="#tax">Tax Category</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='status' ? 'active' : '' ?>" data-bs-toggle="tab" href="#status">Status</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='payroll' ? 'active' : '' ?>" data-bs-toggle="tab" href="#payroll">Payroll Period</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='roles' ? 'active' : '' ?>" data-bs-toggle="tab" href="#roles">User Role</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='create_pass' ? 'active' : '' ?>" data-bs-toggle="tab" href="#create_pass">Biometrics Password</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='requirements_list' ? 'active' : '' ?>" data-bs-toggle="tab" href="#requirements_list">Requirement List</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeTab=='modules' ? 'active' : '' ?>" data-bs-toggle="tab" href="#modules">Modules</a>
                    </li>
                </ul>

                <div class="tab-content mt-3">
                    <div class="tab-pane fade <?= $activeTab=='branch' ? 'show active' : '' ?>" id="branch">
                        <?php include 'BRANCH.php'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='department' ? 'show active' : '' ?>" id="department">
                        <?php include 'DEPARTMENT.php'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='position' ? 'show active' : '' ?>" id="position">
                        <?php include 'POSITION.php'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='level' ? 'show active' : '' ?>" id="level">
                        <?php include 'LEVEL.php'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='tax' ? 'show active' : '' ?>" id="tax">
                        <?php include 'TAX.php'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='status' ? 'show active' : '' ?>" id="status">
                        <?php include 'STATUS.php'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='payroll' ? 'show active' : '' ?>" id="payroll">
                        <?php include 'PAYROLL_PERIODS.php'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='roles' ? 'show active' : '' ?>" id="roles">
                        <?php include 'ROLE.php'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='create_pass' ? 'show active' : '' ?>" id="create_pass">
                        <?php include 'CREATE_PASSWORD.php'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='requirements_list' ? 'show active' : '' ?>" id="requirements_list">
                        <?php include 'REQUIREMENTS_LIST.PHP'; ?>
                    </div>
                    <div class="tab-pane fade <?= $activeTab=='modules' ? 'show active' : '' ?>" id="modules">
                        <?php include 'MODULES.PHP'; ?>
                    </div>
                </div>
            </div>
        </main>
        <?php include __DIR__ . '/layout/FOOTER'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

