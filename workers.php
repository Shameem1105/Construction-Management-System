<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Workers</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Your CSS -->
  <link rel="stylesheet" href="css/sidebar.css?v=6">
  <link rel="stylesheet" href="css/jgc-theme.css?v=6">
  <link rel="stylesheet" href="css/workers.css?v=6">
</head>

<body>

<!-- 🔹 SIDEBAR -->
<?php include 'sidebar.php'; ?>
<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h1 class="page-title">Labour Management</h1>
          <p class="page-sub">Track workers, shifts, and site attendance</p>
        </div>
      </div>

      <div class="topbar-right">
        <select id="siteFilter" class="form-select form-select-sm workers-filter" onchange="applyFilters()" style="border-radius:10px; min-height:38px; font-size:13px; max-width:180px; ">
          <option value="all">All Sites</option>
        </select>

        <button class="tbtn tbtn-primary" onclick="openStep1()">
          <i class="bi bi-plus-lg"></i> Add Worker
        </button>
        
        <div id="currentDate" class="live-date"></div>
      </div>
    </header>

  <div class="workers-shell">

    <!-- STATS -->
    <div class="row g-3 mt-1" id="workerStats"></div>

    <!-- MAIN CARD -->
    <div class="content-card mt-3">
      <div class="section-tabs">
        <button class="section-tab active" data-tab="attendance" onclick="switchTab('attendance')">Attendance</button>
        <button class="section-tab" data-tab="workers" onclick="switchTab('workers')">Worker List</button>
        <button class="section-tab" data-tab="payments" onclick="switchTab('payments')">Payments</button>
      </div>

      <div class="tab-panel active" id="attendancePanel">
        <div class="toolbar-row">
          <div class="toolbar-left">
            <div class="search-wrap">
              <i class="bi bi-search"></i>
              <input type="text" id="workerSearch" class="form-control" placeholder="Search worker..." oninput="applyFilters()">
            </div>

            <select id="statusFilter" class="form-select workers-filter" onchange="applyFilters()">
              <option value="all">All Status</option>
              <option value="present">Present</option>
              <option value="absent">Absent</option>
            </select>
          </div>

          <div class="toolbar-note">
            <i class="bi bi-funnel"></i>
            Live table filtered from worker records
          </div>
        </div>

        <div class="table-wrap">
          <table class="table workers-table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Worker Name</th>
                <th>Role</th>
                <th>Site</th>
                <th>AM</th>
                <th>PM</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="attendanceTableBody"></tbody>
          </table>
        </div>
      </div>

      <div class="tab-panel" id="workersPanel">
        <div class="table-wrap">
          <table class="table workers-table align-middle">
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Site</th>
                <th>Type</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="workersTableBody"></tbody>
          </table>
        </div>
      </div>

      <div class="tab-panel" id="paymentsPanel">
        <div class="empty-state">
          <div class="empty-icon"><i class="bi bi-cash-stack"></i></div>
          <h6>Payments view</h6>
          <p>This section is ready for wage tracking. Add payroll fields when your payments workflow is ready.</p>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- STEP 1: SELECT PROJECT -->
<div id="step1" class="form-modal step">
  <div class="form-box">
    <h5>Select Project</h5>
    <div id="projectList"></div>
    <button class="btn btn-light mt-2 w-100" onclick="closeAll()">Cancel</button>
  </div>
</div>

<!-- STEP 2: SELECT TYPE -->
<div id="step2" class="form-modal step">
  <div class="form-box">
    <h5>Select Type</h5>
    <button class="btn btn-warning w-100 mb-2" onclick="selectType('worker')">👷 Worker</button>
    <button class="btn btn-dark w-100" onclick="selectType('employee')">🧑‍💼 Employee</button>
  </div>
</div>

<!-- STEP 3: INPUT -->
<div id="step3" class="form-modal step">
  <div class="form-box">

    <h5 id="stepTitle">Add Worker (1)</h5>

    <!-- COUNT FIRST -->
    <input id="count" type="number" placeholder="How many?" class="form-control mb-3">

    <button class="btn btn-warning w-100 mb-3" onclick="startAdding()">Start</button>

    <!-- FORM (HIDDEN INITIALLY) -->
    <div id="singleForm" style="display:none;">

      <div class="modal-body">

      <!-- BASIC INFO -->
      <h5 class="section-title">Basic Info</h5>

      <input type="text" id="name" class="form-control mb-2" placeholder="Worker Name">
      <input type="text" id="phone" class="form-control mb-2" placeholder="Phone Number">
      <input type="text" id="role" class="form-control mb-2" placeholder="Role / Position">

      <!-- WORK & WAGE -->
      <h5 class="section-title mt-3">Work & Wage</h5>

      <select id="wage_type" class="form-control mb-2">
        <option value="daily">Daily</option>
        <option value="hourly">Hourly</option>
      </select>

      <input type="number" id="wage_rate" class="form-control mb-2" placeholder="Wage Rate (₹)">
      <input type="number" id="working_hours" class="form-control mb-2" placeholder="Working Hours / Day">

      <!-- SHIFT TIMING -->
      <h5 class="section-title mt-3">Shift Timing</h5>

      <input type="time" id="in_time" class="form-control mb-2">
      <input type="time" id="out_time" class="form-control mb-2">

      <!-- OVERTIME -->
      <h5 class="section-title mt-3">Overtime (OT)</h5>

      <input type="number" id="ot_rate" class="form-control mb-2" placeholder="OT Rate (₹/hour)">
      <input type="number" id="ot_limit" class="form-control mb-2" placeholder="Max OT Hours (optional)">

    </div>

    <div class="modal-footer">
      <button class="btn btn-success" onclick="saveWorker()">Save Worker</button>
    </div>

    </div>

  </div>
</div>

<!-- ATTENDANCE MODAL -->
<div id="attendanceModal" class="form-modal">
  <div class="form-box">
    <h5>Manage Attendance</h5>

    <div id="attendanceList" class="attendance-detail"></div>

    <button class="btn btn-light w-100 mt-3" onclick="closeAttendance()">Close</button>
  </div>
</div>

<script src="js/sidebar.js"></script>
<script src="js/workers.js?v=4"></script>
<script src="js/script1.js"></script>
<script src="js/script.js?v=4"></script>

</body>
</html>