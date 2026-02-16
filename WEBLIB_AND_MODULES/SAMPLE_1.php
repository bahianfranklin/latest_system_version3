<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/CONFIG_2.php";   // API_KEY and COMPANY_KEY
require_once __DIR__ . "/WEBLIB.php";

$weblib = new WebLib();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contacts CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">

  <?php
?>

<div class="modal-header">
    <h5 class="modal-title">Add Contact</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

    <form id="addForm">
        <div class="modal-body">
            <div class="mb-3">
                <label>Full Name</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Address</label>
                <input type="text" name="address" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Contact No</label>
                <input type="text" name="contact_no" class="form-control" required>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-success">Add</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        var table = "contacts";     // change if needed
        var apiURL = "";            // set if needed

        $("[name=btnAdd]").on('click', function () {

            $.ajax({
                type: "POST",
                url: "ajax/process-" + table + ".php",
                data: { api_url: apiURL },
                success: function (r) {
                    $('#mainModal .modal-content').html(r);
                    $('#mainModal').modal('handleUpdate');
                },
                dataType: "html"
            });

            $('#mainModal').modal("show");
        });
    </script>


</body>
</html>