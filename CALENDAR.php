<?php
    session_start();
    require 'db.php';
    require 'audit.php';
    require 'autolock.php';

    if (!isset($_SESSION['user'])) {
        header("Location: LOGIN.php");
        exit();
    }

    $user_id = $_SESSION['user']['id'];

    // Log viewing the calendar page
    logAction(
        $conn,
        $user_id,
        "VIEW CALENDAR",
        "Opened company calendar page"
    );

    $success = "";
    $error = "";

    // ✅ ADD EVENT
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['update_event']) && !isset($_POST['delete_event']) && (!isset($_POST['action']) || $_POST['action'] !== 'delete')) {
        $event_type  = $_POST['event_type'];
        $title       = trim($_POST['title']);
        $date        = $_POST['date'];
        $location    = trim($_POST['location']);
        $description = trim($_POST['description']);
        $visibility  = $_POST['visibility'];

        $stmt = $conn->prepare("INSERT INTO holidays (event_type, title, date, location, description, visibility) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", $event_type, $title, $date, $location, $description, $visibility);
            if ($stmt->execute()) {
                logAction(
                    $conn,
                    $user_id,
                    "ADD CALENDAR EVENT",
                    "Event Type: $event_type, Title: $title, Date: $date"
                );

                $success = "✅ Event added successfully!";
                header("Location: CALENDAR.php?success=1");
                exit();
            } else {
                $error = "❌ Insert failed: " . $stmt->error;
                header("Location: CALENDAR.php?success=1");
                exit();
            }
            
        } else {
            $error = "❌ SQL Prepare failed: " . $conn->error;
            header("Location: CALENDAR.php?success=1");
            exit();
        }
    }

    // ✅ UPDATE EVENT
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_event'])) {
        $id          = intval($_POST['id']);
        $event_type  = $_POST['event_type'];
        $title       = trim($_POST['title']);
        $date        = $_POST['date'];
        $location    = trim($_POST['location']);
        $description = trim($_POST['description']);
        $visibility  = $_POST['visibility'];

        $stmt = $conn->prepare("UPDATE holidays 
                                SET event_type=?, title=?, date=?, location=?, description=?, visibility=? 
                                WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("ssssssi", $event_type, $title, $date, $location, $description, $visibility, $id);
            if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                logAction(
                    $conn,
                    $user_id,
                    "UPDATE CALENDAR EVENT",
                    "ID: $id, Event Type: $event_type, Title: $title, Date: $date"
                );

                $success = "✅ Event updated successfully!";
                header("Location: CALENDAR.php?success=1");
                exit();
            } else {
                $error = "⚠️ No changes made. Either the ID does not exist or the data is the same.";
                header("Location: CALENDAR.php?success=1");
                exit();
            }
            } else {
                $error = "❌ Update failed: " . $stmt->error;
                header("Location: CALENDAR.php?success=1");
                exit();
            }
        }
    }

    // ✅ DELETE EVENT
    if ($_SERVER["REQUEST_METHOD"] === "POST" && (isset($_POST['delete_event']) || (isset($_POST['action']) && $_POST['action'] === 'delete'))) {
        $id = intval($_POST['id']);

        $stmt = $conn->prepare("DELETE FROM holidays WHERE id=?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                logAction(
                    $conn,
                    $user_id,
                    "DELETE CALENDAR EVENT",
                    "Deleted Event ID: $id"
                );

                if (isset($_POST['action'])) {
                    echo json_encode(["status" => "success"]);
                    exit;
                }
                $success = "🗑️ Event deleted successfully!";
            } else {
                if (isset($_POST['action'])) {
                    echo json_encode(["status" => "error", "message" => $stmt->error]);
                    exit;
                }
                $error = "❌ Delete failed: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "❌ SQL Prepare failed: " . $conn->error;
        }
    }

?>
    <?php include __DIR__ . '/layout/HEADER'; ?>
    <?php include __DIR__ . '/layout/NAVIGATION'; ?>

     <!-- Bootstrap & Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <!-- Custom Styles -->
    <link href="css/styles.css" rel="stylesheet" />

    <style>
        /* Sidebar & Layout */
        #layoutSidenav {
            display: flex;
            min-height: 100vh;
        }

        #layoutSidenav_nav {
            width: 250px;
            flex-shrink: 0;
            transition: margin-left 0.3s ease;
        }

        #layoutSidenav_content {
            flex-grow: 1;
            min-width: 0;
            transition: margin 0.3s ease;
            padding: 20px;
            box-sizing: border-box;
        }

        /* Calendar container */
        #calendar {
            font-family: Arial, sans-serif;
            width: 100%;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 10px rgba(0,0,0,.08);
        }

        /* Legend */
        #calendar-legend {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-right: 20px;
            padding: 15px;
            font-size: 14px;
            flex: 0 0 200px;
        }

        /* Event styling */
        .fc-event,
        .fc-event-title {
            font-size: 11px;
            line-height: 1.2;
        }

        /* Responsive Fix */
        @media (max-width: 992px) {
            #calendar-legend {
                display: none;   /* hide legend on small screens */
            }
            #layoutSidenav_nav {
                width: 200px;
            }
        }
    </style>
	
    <div id="layoutSidenav_content">
        <main>
            <div class="container mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">Company Calendar</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">➕ Add Event</button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <!-- ✅ Add Event Modal -->
                <div class="modal fade" id="addEventModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="CALENDAR.php">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add New Event</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">Event Type</label>
                                    <select name="event_type" class="form-select" required>
                                        <option value="Birthday">Birthday</option>
                                        <option value="Holiday">Holiday</option>
                                        <option value="Custom">Custom</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <label class="form-label mt-2">Title</label>
                                    <input type="text" name="title" class="form-control" required>
                                    <label class="form-label mt-2">Date</label>
                                    <input type="date" name="date" class="form-control" required>
                                    <label class="form-label mt-2">Location</label>
                                    <input type="text" name="location" class="form-control">
                                    <label class="form-label mt-2">Description</label>
                                    <textarea name="description" class="form-control"></textarea>
                                    <label class="form-label mt-2">Visibility</label>
                                    <select name="visibility" class="form-select">
                                        <option value="Public">Public</option>
                                        <option value="Private">Private</option>
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">Save Event</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <div style="display: flex;">
                <!-- Legend -->
                <div id="calendar-legend" style="width: 200px; padding: 10px; font-family: Arial; font-size: 14px;">
                    <h3 style="margin-bottom: 10px; margin-top: 150px">Legend</h3>
                    <div style="margin-bottom: 8px;">
                    <span style="display:inline-block; width:16px; height:16px; background:#f39c12; margin-right:6px; border-radius:3px;"></span>
                    Birthday 🎂
                    </div>
                    <div style="margin-bottom: 8px;">
                    <span style="display:inline-block; width:16px; height:16px; background:#28a745; margin-right:6px; border-radius:3px;"></span>
                    Holiday 🎉
                    </div>
                    <div style="margin-bottom: 8px;">
                    <span style="display:inline-block; width:16px; height:16px; background:#007bff; margin-right:6px; border-radius:3px;"></span>
                    Schedule Leaves 📌
                    </div>
                    <div style="margin-bottom: 8px;">
                    <span style="display:inline-block; width:16px; height:16px; background:#6c757d; margin-right:6px; border-radius:3px;"></span>
                    Other 📅
                    </div>
                </div>

                <!-- Calendar -->
                <div id="calendar" style="flex-grow: 1;"></div>

                <!-- ✅ Edit Event Modal -->
                <div class="modal fade" id="editEventModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="CALENDAR.php">
                                <input type="hidden" name="update_event" value="1">
                                <input type="hidden" name="id" id="edit-id">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Event</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">Event Type</label>
                                    <select name="event_type" id="edit-event_type" class="form-select" required></select>
                                    <label class="form-label mt-2">Title</label>
                                    <input type="text" name="title" id="edit-title" class="form-control" required>
                                    <label class="form-label mt-2">Date</label>
                                    <input type="date" name="date" id="edit-date" class="form-control" required>
                                    <label class="form-label mt-2">Location</label>
                                    <input type="text" name="location" id="edit-location" class="form-control">
                                    <label class="form-label mt-2">Description</label>
                                    <textarea name="description" id="edit-description" class="form-control"></textarea>
                                    <label class="form-label mt-2">Visibility</label>
                                    <select name="visibility" id="edit-visibility" class="form-select"></select>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">Update Event</button>
                                    <button type="button" class="btn btn-danger" id="deleteEventBtn">Delete</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <?php include __DIR__ . '/layout/FOOTER.php'; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/rrule@2.6.4/dist/es5/rrule.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/rrule@6.1.11/index.global.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: 'dayGridMonth',
                events: 'FETCH-BIRTHDAYS.php',
                eventClick: function(info) {
                    const event = info.event;
                    document.getElementById('edit-id').value = event.id; // ✅ not extendedProps.id
                    document.getElementById('edit-title').value = event.title;
                    document.getElementById('edit-date').value = event.startStr;
                    document.getElementById('edit-location').value = event.extendedProps.location || '';
                    document.getElementById('edit-description').value = event.extendedProps.description || '';

                    document.getElementById('edit-event_type').innerHTML = `
                        <option ${event.extendedProps.event_type=='Birthday'?'selected':''}>Birthday</option>
                        <option ${event.extendedProps.event_type=='Holiday'?'selected':''}>Holiday</option>
                        <option ${event.extendedProps.event_type=='Custom'?'selected':''}>Custom</option>
                        <option ${event.extendedProps.event_type=='Other'?'selected':''}>Other</option>
                    `;
                    document.getElementById('edit-visibility').innerHTML = `
                        <option ${event.extendedProps.visibility=='Public'?'selected':''}>Public</option>
                        <option ${event.extendedProps.visibility=='Private'?'selected':''}>Private</option>
                    `;
                    new bootstrap.Modal(document.getElementById('editEventModal')).show();
                }
            });
            calendar.render();

            // ✅ delete function
            document.getElementById('deleteEventBtn').addEventListener('click', function () {
                if (!confirm("Are you sure you want to delete this event?")) return;

                const eventId = document.getElementById('edit-id').value;

                fetch('CALENDAR.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'delete',
                        id: eventId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        alert("Event deleted!");
                        const modalEl = document.getElementById('editEventModal');
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();
                        calendar.refetchEvents();
                    } else {
                        alert("Delete failed: " + data.message);
                    }
                })
                .catch(err => console.error(err));
            });
        });
    </script>

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