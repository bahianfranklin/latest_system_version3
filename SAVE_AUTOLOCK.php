<?php
session_start();
require 'db.php';

// Read current settings to prefill the form
$current = ['autolock_status' => 'OFF', 'minutes' => 0];
$sel = $conn->prepare("SELECT autolock_status, minutes FROM autolock_settings WHERE id = 1 LIMIT 1");
if ($sel) {
    $sel->execute();
    $res = $sel->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if ($row) {
        $current['autolock_status'] = $row['autolock_status'] ?? 'OFF';
        $current['minutes'] = (int)($row['minutes'] ?? 0);
    }
    $sel->close();
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status  = ($_POST['autolock_status'] ?? 'OFF') === 'ON' ? 'ON' : 'OFF';
    $minutes = isset($_POST['minutes']) ? (int) $_POST['minutes'] : 0;

    // Validation
    if ($status === 'ON' && $minutes < 1) {
        $error = 'Minutes is required and must be at least 1 when Auto-Lock is ON.';
    } else {
        $sql = "INSERT INTO autolock_settings (id, autolock_status, minutes)
                VALUES (1, ?, ?)
                ON DUPLICATE KEY UPDATE autolock_status = VALUES(autolock_status), minutes = VALUES(minutes)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param('si', $status, $minutes);
            if ($stmt->execute()) {
                $stmt->close();
                // Redirect to avoid resubmit and show success message
                header('Location: save_autolock.php?success=1');
                exit;
            } else {
                $error = 'Update error: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
    // If not redirected, update $current so form shows submitted values
    $current['autolock_status'] = $status;
    $current['minutes'] = $minutes;
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Settings</title>
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    </head>
    <body class="container mt-4">
        <h4>Account Settings</h4>
        <br>
        <form action="save_autolock.php" method="POST" class="p-3 border rounded">

            <h5>System Auto-Lock Settings</h5>
            <hr>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <!-- Radio Buttons -->
            <div class="mb-3">
                <label class="form-label fw-bold">Auto-Lock:</label><br>

                <input type="radio" name="autolock_status" value="ON" id="on"
                    <?= $current['autolock_status'] === 'ON' ? 'checked' : '' ?>>
                <label for="on">ON</label> &nbsp;&nbsp;

                <input type="radio" name="autolock_status" value="OFF" id="off"
                    <?= $current['autolock_status'] === 'OFF' ? 'checked' : '' ?>>
                <label for="off">OFF</label>
            </div>

            <!-- Minutes textbox -->
            <div class="mb-3">
                <label class="form-label fw-bold">Enter No. of Minutes:</label>
                <input type="number" name="minutes" class="form-control" placeholder="Enter minutes…" min="1" value="<?= htmlspecialchars($current['minutes']) ?>">
            </div>

            <!-- Save Button -->
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="INDEX.PHP" class="btn btn-secondary">Back</a>

        </form>
    </body>
    
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const onRadio = document.getElementById("on");
            const offRadio = document.getElementById("off");
            const minutesField = document.getElementById("minutesField");

            function applyState() {
                if (offRadio.checked) {
                    minutesField.value = 0;
                    minutesField.disabled = true;
                } else {
                    minutesField.disabled = false;
                    if (minutesField.value == 0) {
                        minutesField.value = "";
                    }
                }
            }

            offRadio.addEventListener("change", function () {
                let confirmOff = confirm("Are you sure you want to turn OFF Auto-Lock?");
                if (confirmOff) {
                    minutesField.value = 0;
                    minutesField.disabled = true;
                } else {
                    onRadio.checked = true;
                    applyState();
                }
            });

            onRadio.addEventListener("change", function () {
                minutesField.disabled = false;
            });

            applyState();
        });
    </script>
</html>

