<?php
include "db.php";

if (isset($_POST['add_material'])) {
  $material_name = mysqli_real_escape_string($conn, $_POST['material_name']);
  $unit = mysqli_real_escape_string($conn, $_POST['unit']);
  $category = mysqli_real_escape_string($conn, $_POST['category']);
  $minimum_stock = mysqli_real_escape_string($conn, $_POST['minimum_stock']);

  $sql = "INSERT INTO materials 
  (material_name, unit, category, minimum_stock)
  VALUES
  ('$material_name', '$unit', '$category', '$minimum_stock')";

  if (mysqli_query($conn, $sql)) {
    echo "<script>alert('Material Added Successfully');</script>";
  } else {
    echo mysqli_error($conn);
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Materials</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="css/jgc-theme.css?v=6">
<link rel="stylesheet" href="css/sidebar.css?v=6">
<link rel="stylesheet" href="css/materials.css?v=6">
</head>

<body>

<!-- ═══════════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════════════ -->
<?php include 'sidebar.php'; ?>

<!-- ═══════════════════════════════════════════════
     MAIN CONTENT
════════════════════════════════════════════════ -->
<div class="main">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <h1 class="page-title">Materials</h1>
        <p class="page-sub">Manage materials &amp; stock</p>
      </div>
    </div>

    <div class="topbar-right">
      <div class="top-controls d-flex align-items-center gap-2">
        <div class="date-range" style="font-size: 13px; color:#64748b; margin-right: 8px;">01 May 2024 – 16 May 2024</div>
        <button class="tbtn tbtn-light">All Sites</button>
        <button class="tbtn tbtn-primary" onclick="openStep1()"><i class="bi bi-plus-lg"></i> New Transaction</button>
      </div>
      <div id="currentDate" class="live-date"></div>
    </div>
  </header>

  <!-- METRIC CARDS -->
  <div class="dashboard-shell">
    <div class="dashboard-metrics">
      <div class="stat-card">
        <div class="stat-icon icon-blue"><i class="bi bi-currency-rupee"></i></div>
        <div>
          <div class="stat-title">Total Stock Value</div>
          <div class="stat-value" id="totalStockValue">₹0</div>
          <div class="stat-sub">All Sites</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-purple"><i class="bi bi-boxes"></i></div>
        <div>
          <div class="stat-title">Total Items</div>
          <div class="stat-value" id="totalItems">0</div>
          <div class="stat-sub">All Materials</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-yellow"><i class="bi bi-exclamation-triangle"></i></div>
        <div>
          <div class="stat-title">Low Stock Items</div>
          <div class="stat-value" id="lowStockCount">0</div>
          <div class="stat-sub">Need Attention</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-green"><i class="bi bi-arrow-down-circle"></i></div>
        <div>
          <div class="stat-title">Stock In (This Month)</div>
          <div class="stat-value" id="stockInMonth">₹0</div>
          <div class="stat-sub">% change</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon icon-red"><i class="bi bi-arrow-up-circle"></i></div>
        <div>
          <div class="stat-title">Stock Out (This Month)</div>
          <div class="stat-value" id="stockOutMonth">₹0</div>
          <div class="stat-sub">% change</div>
        </div>
      </div>
    </div>

    <!-- DASHBOARD GRID -->
    <div class="dashboard-grid">

      <!-- LEFT COLUMN -->
      <div class="dashboard-main">

        <!-- Stock Summary Table -->
        <div class="card main-card w-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6>Stock Summary</h6>
            <div class="search-wrap">
              <input id="searchMaterial" class="form-control form-control-sm" placeholder="Search material...">
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm" id="stockSummaryTable">
                <thead>
                  <tr>
                    <th>Material</th>
                    <th>Unit</th>
                    <th>Main Warehouse In</th>
                    <th>Out</th>
                    <th>Balance</th>
                    <th>Total Balance</th>
                    <th>Stock Value (₹)</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div class="mt-3">
              <nav><ul class="pagination pagination-sm" id="pagination"></ul></nav>
            </div>
          </div>
        </div>

        <!-- Lower Grid: Recent Transactions + Purchase Summary -->
        <div class="dashboard-lower-grid w-100">
          <div class="card">
            <div class="card-header"><h6>Recent Transactions</h6></div>
            <div class="card-body" id="recentTransactions">Loading...</div>
          </div>
          <div class="card">
            <div class="card-header"><h6>Purchase Summary (This Month)</h6></div>
            <div class="card-body" id="purchaseSummary">—</div>
          </div>
        </div>

      </div><!-- /.dashboard-main -->

      <!-- RIGHT COLUMN (sidebar) -->
      <div class="dashboard-sidebar">

        <!-- Chart -->
        <div class="card chart-card w-100">
          <div class="card-body">
            <h6>Stock Value by Category</h6>
            <canvas id="stockValueChart"></canvas>
            <div class="legend mt-3" id="chartLegend"></div>
          </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="card w-100">
          <div class="card-body">
            <h6>Low Stock Alerts</h6>
            <div id="lowStockAlerts">—</div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card w-100">
          <div class="card-body">
            <h6>Quick Actions</h6>
            <div class="quick-actions">
              <button class="btn btn-outline-primary" onclick="openStep1()">Purchase</button>
              <button class="btn btn-outline-secondary">Stock Transfer</button>
              <button class="btn btn-outline-success" onclick="openAddMaterial()">Add Material</button>
            </div>
          </div>
        </div>

      </div><!-- /.dashboard-sidebar -->

    </div><!-- /.dashboard-grid -->
  </div><!-- /.dashboard-shell -->

</div><!-- /.main -->


<!-- ═══════════════════════════════════════════════
     OVERLAY MODALS  (hidden by default via .step)
     These are the ONLY place step1/step2/detailsModal
     exist — as fixed overlays, NOT inline sections.
════════════════════════════════════════════════ -->

<!-- STEP 1 – Select Project -->
<div class="step" id="step1">
  <div class="step-box">
    <div class="step-box-header">
      <h6><i class="bi bi-grid me-2"></i>Select Project</h6>
      <button class="step-close" onclick="closeAll()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="step-box-body">
      <p class="step-hint">Choose the project to add a material transaction for.</p>
      <div id="projectList" class="project-list"></div>
    </div>
    <div class="step-box-footer">
      <button class="btn btn-sm btn-outline-secondary" onclick="closeAll()">Cancel</button>
    </div>
  </div>
</div>

<!-- STEP 2 – Add / Edit Material -->
<div class="step" id="step2">
  <div class="step-box">
    <div class="step-box-header">
      <h6><i class="bi bi-box me-2"></i>Material Details</h6>
      <button class="step-close" onclick="closeAll()"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST" action="">
      <div class="step-box-body">
        <div class="mb-3">
          <label class="form-label">Material Name</label>
          <input type="text" id="materialName" name="material_name" class="form-control" placeholder="e.g. Cement, Steel…">
        </div>
        <div class="mb-3">
          <label class="form-label">Category</label>
          <input type="text" id="category" name="category" class="form-control" placeholder="e.g. Cement, Steel…">
        </div>
        <div class="mb-3">
          <label class="form-label">Minimum Stock</label>
          <input type="number" id="quantity" name="minimum_stock" class="form-control" placeholder="0" min="0">
        </div>
        <div class="mb-3">
          <label class="form-label">Unit</label>
          <select id="unit" name="unit" class="form-select">
            <option value="">— Select unit —</option>
            <option value="kg">kg</option>
            <option value="ton">ton</option>
            <option value="litre">litre</option>
            <option value="bag">bag</option>
            <option value="piece">piece</option>
            <option value="bundle">bundle</option>
            <option value="cu.ft">cu.ft</option>
            <option value="sq.ft">sq.ft</option>
            <option value="metre">metre</option>
            <option value="unit">unit</option>
          </select>
        </div>
      </div>
      <div class="step-box-footer">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="closeAll()">Cancel</button>
        <button type="submit" name="add_material" class="btn btn-sm btn-primary" id="btnAdd">Add Material</button>
        <button type="button" class="btn btn-sm btn-success" onclick="finishMaterials()" id="btnDone" style="display:none;">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- DETAILS MODAL – View / Edit / Delete existing material -->
<div class="step" id="detailsModal">
  <div class="step-box">
    <div class="step-box-header">
      <h6><i class="bi bi-info-circle me-2"></i>Material Details</h6>
      <button class="step-close" onclick="closeDetails()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="step-box-body" id="detailsContent">
      <!-- populated by JS -->
    </div>
    <div class="step-box-footer">
      <button class="btn btn-sm btn-outline-secondary" onclick="closeDetails()">Close</button>
    </div>
  </div>
</div>


<!-- ═══════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════════ -->
<script src="js/sidebar.js"></script>
<script src="js/script1.js"></script>
<script src="js/script.js?v=4"></script>
<script src="js/materials.js?v=4"></script>

<!-- Patch: expose openAddMaterial helper so Quick Actions "Add Material"
     skips project selection and goes straight to step2             -->
<script>
function openAddMaterial() {
  selectedProjectId = null;
  document.getElementById('btnAdd').style.display  = 'inline-block';
  document.getElementById('btnDone').style.display = 'none';
  clearInputs();
  document.getElementById('step2').classList.add('active');
}
</script>

</body>
</html>
