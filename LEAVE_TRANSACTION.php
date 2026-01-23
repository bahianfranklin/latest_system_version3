<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container mt-4">
            <h4>Leave Transactions</h4>

            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <label>Leave Year:</label>
                    <select id="leaveYear" class="form-control">
                        <option value="">All</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Leave Type:</label>
                    <select id="leaveType" class="form-control">
                        <option value="All">All</option>
                        <option value="Mandatory Leave">Mandatory Leave</option>
                        <option value="Vacation Leave">Vacation Leave</option>
                        <option value="Sick Leave">Sick Leave</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Search:</label>
                    <input type="text" id="searchBox" class="form-control" placeholder="Search by leave no. or remarks...">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="loadLeaveTransactions()">Search</button>
                </div>
            </div>

            <!-- Table -->
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Leave Type</th>
                        <th>Transaction Type</th>
                        <th>Value</th>
                        <th>Remarks</th>
                        <th>Date/Time Created</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody id="leaveTableBody">
                    <!-- Rows will load here -->
                </tbody>
            </table>
        </div>
    </main>

    <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>

<!-- Scripts -->
<script>
function loadLeaveTransactions() {
    const year = document.getElementById("leaveYear").value;
    const type = document.getElementById("leaveType").value;
    const search = document.getElementById("searchBox").value;

    console.log("Loading with:", { year, type, search });

    fetch(`leave_transaction_data.php?year=${encodeURIComponent(year)}&type=${encodeURIComponent(type)}&search=${encodeURIComponent(search)}`)
        .then(response => {
            console.log("Response status:", response.status);
            return response.text(); // Use text() to see what's actually returned
        })
        .then(text => {
            console.log("Raw response:", text);
            try {
                const data = JSON.parse(text);
                console.log("Parsed data:", data);
                
                const tbody = document.getElementById("leaveTableBody");
                tbody.innerHTML = "";

                if (data.error) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: ${data.error}</td></tr>`;
                    return;
                }

                if (!Array.isArray(data) || data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center">No records found</td></tr>`;
                    return;
                }

                data.forEach(row => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td>${row.leave_type || 'N/A'}</td>
                        <td>${row.transaction_type || 'N/A'}</td>
                        <td>${row.credit_value || 'N/A'}</td>
                        <td>${row.remarks || 'N/A'}</td>
                        <td>${row.date_applied || 'N/A'}</td>
                        <td>${row.created_by || 'N/A'}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } catch (e) {
                console.error("JSON parse error:", e);
                document.getElementById("leaveTableBody").innerHTML = `<tr><td colspan="6" class="text-center text-danger">Parse Error: ${e.message}</td></tr>`;
            }
        })
        .catch(err => {
            console.error("Fetch error:", err);
            document.getElementById("leaveTableBody").innerHTML = `<tr><td colspan="6" class="text-center text-danger">Fetch Error: ${err.message}</td></tr>`;
        });
}
</script>

<!-- Optional: Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<script>
// Load data on page load
document.addEventListener("DOMContentLoaded", function() {
    loadLeaveTransactions();
});
</script>

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

