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
            <div class="col-md-4">
                <input type="text" id="search" class="form-control form-control-sm" placeholder="Search...">
            </div>

            <div class="col-md-4">

                <div class="dropdown">
                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Column Visibility
                    </button>

                    <div class="dropdown-menu p-3">

                        <div class="form-check">
                            <input class="form-check-input column-toggle" type="checkbox" value="1" checked>
                            <label class="form-check-label">Full Name</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input column-toggle" type="checkbox" value="2" checked>
                            <label class="form-check-label">Address</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input column-toggle" type="checkbox" value="3" checked>
                            <label class="form-check-label">Contact No</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input column-toggle" type="checkbox" value="4" checked>
                            <label class="form-check-label">Actions</label>
                        </div>

                    </div>
                </div>

            </div>

            <div class="col-md-4 text-end">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                    Add Contact
                </button>
            </div>
        </div>

        <div id="tableContainer">

            <!-- ADD MODAL -->
            <div class="modal fade" id="addModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addForm">
                            <div class="modal-header">
                                <h5 class="modal-title">Add Contact</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <input type="text" name="fullname" class="form-control mb-2" placeholder="Full Name"
                                    required>
                                <input type="text" name="address" class="form-control mb-2" placeholder="Address"
                                    required>
                                <input type="text" name="contact_no" class="form-control" placeholder="Contact No"
                                    required>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-success btn-sm">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- EDIT MODAL -->
            <div class="modal fade" id="editModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="editForm">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Contact</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <input type="hidden" name="record_id" id="edit_id">

                                <input type="text" name="fullname" id="edit_fullname" class="form-control mb-2"
                                    required>

                                <input type="text" name="address" id="edit_address" class="form-control mb-2" required>

                                <input type="text" name="contact_no" id="edit_contact" class="form-control" required>
                            </div>

                            <div class="modal-footer">
                                <button class="btn btn-primary btn-sm">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
                $(document).on("change", "#limit", function () {
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
                            { id: $(this).data("id") },
                            function (res) {
                                alert(res);
                                loadTable();
                            });
                    }
                });

            });

            function applyColumnVisibility() {

                $(".column-toggle").each(function () {

                    let column = $(this).val();

                    if ($(this).is(":checked")) {
                        $(".col-" + column).show();
                    } else {
                        $(".col-" + column).hide();
                    }

                });

            }

        </script>


</body>

</html>