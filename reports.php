<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="css/sidebar.css?v=6">
<link rel="stylesheet" href="css/jgc-theme.css?v=6">
<link rel="stylesheet" href="css/reports.css?v=6">
</head>

<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>



<!-- Main -->
<div class="main">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-sub">Project analytics & insights</p>
      </div>
    </div>

    <div class="topbar-right">
      <button class="tbtn tbtn-primary" onclick="openUpdate()"><i class="bi bi-pencil-square"></i> Update Reports</button>
      <div id="currentDate" class="live-date"></div>
    </div>
  </header>

  <!-- Cards -->
  <div class="row g-4 mt-3" id="reportsContainer"></div>

</div>

<!-- DETAILS MODAL -->
<div id="reportDetails" class="details-modal">
  <div class="details-box">
    <span class="close-btn" onclick="closeDetails()">×</span>
    <h4 id="reportTitle"></h4>
    <div id="reportContent"></div>
  </div>
</div>

<!-- UPDATE MODAL -->
<div id="updateModal" class="form-modal">
  <div class="form-box">

    <h5>Update Report</h5>

    <select id="reportProject" class="form-control mb-2"></select>

    <input id="reportExpense" type="number" placeholder="Total Expense" class="form-control mb-2">
    <input id="reportBudget" type="number" placeholder="Budget" class="form-control mb-2">
    <input id="reportProgress" type="number" placeholder="Progress %" class="form-control mb-2">
    <input id="reportDays" type="number" placeholder="Days Remaining" class="form-control mb-3">

    <button class="btn btn-warning w-100 mb-2" onclick="saveReport()">Save</button>
    <button class="btn btn-light w-100" onclick="closeUpdate()">Cancel</button>

  </div>
</div>

<script src="js/sidebar.js"></script>
<script src="js/script1.js?v=10"></script>
<script src="js/script.js?v=4"></script>
<script src="js/reports.js?v=11"></script>

</body>
</html>