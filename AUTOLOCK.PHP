<?php
if (!isset($conn)) {
    require 'db.php';
}

$auto = $conn->query("SELECT autolock_status, minutes FROM autolock_settings WHERE id = 1");
$setting = $auto->fetch_assoc();

$autoStatus = $setting['autolock_status'] ?? 'OFF';
$autoMinutes = $setting['minutes'] ?? 0;

// Auto-logout backend
if ($autoStatus === "ON") {
    $timeout = $autoMinutes * 60;

    if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
        session_unset();
        session_destroy();
        header("Location: lockscreen.php?reason=timeout");
        exit();
    }

    $_SESSION['last_activity'] = time();
}
?>

<script>
<?php if ($autoStatus === "ON") : ?>
let autoMinutes = <?= $autoMinutes ?>;
let autoLockMillis = autoMinutes * 60 * 1000;

let timer = setTimeout(() => {
    window.location.href = "LOCKSCREEN.php?reason=autolock";
}, autoLockMillis);

document.addEventListener("mousemove", resetTimer);
document.addEventListener("keypress", resetTimer);
document.addEventListener("click", resetTimer);

function resetTimer() {
    clearTimeout(timer);
    timer = setTimeout(() => {
        window.location.href = "LOCKSCREEN.php?reason=autolock";
    }, autoLockMillis);
}
<?php endif; ?>
</script>
