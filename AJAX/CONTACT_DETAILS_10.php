<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Contacts CRUD (AJAX)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">

    <div class="container">
        <h2>Contacts List</h2>

        <div class="row mb-3">
            <div class="col-md-6">
                <input type="text" id="search" class="form-control" placeholder="Search name, address or contact...">
            </div>

            <div class="col-md-3">
                <select id="limit" class="form-select">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="all">All</option>
                </select>
            </div>

            <div class="col-md-3 text-end">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                    Add Contact
                </button>
            </div>
        </div>

        <div id="tableContainer"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {

            loadTable();

            function loadTable(page = 1) {
                $.ajax({
                    url: "FETCH_CONTACTS.php",
                    type: "GET",
                    data: {
                        page: page,
                        search: $("#search").val(),
                        limit: $("#limit").val()
                    },
                    success: function (response) {
                        $("#tableContainer").html(response);
                    }
                });
            }

            // Search
            $("#search").keyup(function () {
                loadTable();
            });

            // Limit change
            $("#limit").change(function () {
                loadTable();
            });

            // Pagination click
            $(document).on("click", ".page-link", function (e) {
                e.preventDefault();
                let page = $(this).data("page");
                loadTable(page);
            });

            // Add
            $("#addForm").submit(function (e) {
                e.preventDefault();
                $.post("CRUD.php?action=add", $(this).serialize(), function (res) {
                    alert(res);
                    $("#addModal").modal('hide');
                    loadTable();
                });
            });

            // Edit
            $(document).on("click", ".editBtn", function () {
                $("#edit_id").val($(this).data("id"));
                $("#edit_fullname").val($(this).data("fullname"));
                $("#edit_address").val($(this).data("address"));
                $("#edit_contact").val($(this).data("contact"));
            });

            $("#editForm").submit(function (e) {
                e.preventDefault();
                $.post("CRUD.php?action=edit", $(this).serialize(), function (res) {
                    alert(res);
                    $("#editModal").modal('hide');
                    loadTable();
                });
            });

            // Delete
            $(document).on("click", ".deleteBtn", function () {
                if (confirm("Delete this contact?")) {
                    $.post("CRUD.php?action=delete",
                        { record_id: $(this).data("id") },
                        function (res) {
                            alert(res);
                            loadTable();
                        });
                }
            });

        });
    </script>
</body>

</html>