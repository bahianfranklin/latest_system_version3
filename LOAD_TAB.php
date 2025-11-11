<?php
    // load_tab.php - Loads tab content dynamically
    $tab = $_GET['tab'] ?? '';
    
    // Whitelist allowed tabs to prevent arbitrary file inclusion
    $allowed_tabs = [
        'USERS.php',
        'SCHEDULES.php',
        'LEAVE_CREDIT.php',
        'WORKING_HOURS.php',
        '201_FILE.php',
        'SALARY_RATE.PHP',
        'APPROVER_MAINTENANCE.php',
        'ROLE_ACCESS_MANAGEMENT.php'
    ];

    if (in_array($tab, $allowed_tabs) && file_exists($tab)) {
        include $tab;
    } else {
        echo "<p class='text-danger'>Error: Tab not found</p>";
    }
?>
