<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Construction Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="css/jgc-theme.css?v=6">
<link rel="stylesheet" href="css/sidebar.css?v=6">
<link rel="stylesheet" href="css/dashboard.css?v=6">
</head>

<body>

<!-- ══════════════════════════════════════
     SIDEBAR
══════════════════════════════════════ -->
<?php include 'sidebar.php'; ?>

<!-- ══════════════════════════════════════
     MAIN
══════════════════════════════════════ -->
<div class="main">
<div class="dashboard-container">

  <!-- ── TOPBAR ───────────────────────── -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-sub">Welcome back, Ramesh! Here's what's happening on your sites.</p>
      </div>
    </div>

    <div class="topbar-right">
      <div class="topbar-filters">
        <div class="select-wrap">
          <i class="bi bi-building"></i>
          <select id="projectFilter" class="tselect"><option>All Projects</option></select>
        </div>
        <div class="select-wrap">
          <i class="bi bi-calendar3"></i>
          <select id="timeFilter" class="tselect"><option>This Month</option></select>
        </div>
        <button class="tbtn tbtn-ghost"><i class="bi bi-sliders"></i> Filters</button>
        <button class="tbtn tbtn-primary"><i class="bi bi-plus-lg"></i> Add</button>
        <button class="tbtn tbtn-light"><i class="bi bi-upload"></i> Upload</button>
      </div>
      <div id="currentDate" class="live-date"></div>
    </div>
  </header>

  
  <section class="quick-grid">
    <a href="projects.php" class="quick-card">
      <div class="quick-icon qc-blue"><i class="bi bi-building-add"></i></div>
      <div><div class="quick-title">Add Project</div><div class="quick-desc">Create a new construction project</div></div>
    </a>
    <a href="income_expense.php" class="quick-card">
      <div class="quick-icon qc-green"><i class="bi bi-currency-rupee"></i></div>
      <div><div class="quick-title">Add Expense</div><div class="quick-desc">Track site expenses</div></div>
    </a>
    <a href="#" class="quick-card">
      <div class="quick-icon qc-amber"><i class="bi bi-upload"></i></div>
      <div><div class="quick-title">Upload Photo</div><div class="quick-desc">Add site photos</div></div>
    </a>
    <a href="workers.php" class="quick-card">
      <div class="quick-icon qc-violet"><i class="bi bi-person-plus"></i></div>
      <div><div class="quick-title">Labour</div><div class="quick-desc">Manage labour &amp; attendance</div></div>
    </a>
  </section>

  <!-- ── KPI METRIC CARDS ─────────────── -->
  <section class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon ki-blue"><i class="bi bi-building"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Total Projects</div>
        <div class="kpi-value" id="overviewProjectsValue">0</div>
        <div class="kpi-sub">All sites combined</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon ki-green"><i class="bi bi-geo-alt"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Active Sites</div>
        <div class="kpi-value" id="overviewWorkersValue">0</div>
        <div class="kpi-sub">Currently running</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon ki-amber"><i class="bi bi-bar-chart-line"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Total Cost</div>
        <div class="kpi-value" id="overviewMaterialsValue">0</div>
        <div class="kpi-sub">Materials tracked</div>
      </div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon ki-red"><i class="bi bi-clock-history"></i></div>
      <div class="kpi-body">
        <div class="kpi-label">Pending Amount</div>
        <div class="kpi-value" id="overviewExpenseValue">₹0</div>
        <div class="kpi-sub">Awaiting clearance</div>
      </div>
    </div>
  </section>

  <!-- ── FINANCIAL + PROJECT SUMMARY ──── -->
  <section class="mid-grid">

    <!-- Financial Snapshot -->
    <div class="panel-card financial-card">
      <div class="panel-head">
        <h3><i class="bi bi-graph-up-arrow"></i> Financial Snapshot</h3>
        <span class="head-badge">Live</span>
      </div>
      <div class="fin-row">
        <div class="fin-stat">
          <div class="fin-label">Budget</div>
             <div class="fin-value" id="budgetTotalValue">₹0</div>
        </div>
        <div class="fin-divider"></div>
        <div class="fin-stat">
          <div class="fin-label">Spent</div>
              <div class="fin-value text-danger" id="budgetSpentValue">₹0</div>
        </div>
        <div class="fin-divider"></div>
        <div class="fin-stat">
          <div class="fin-label">Remaining</div>
              <div class="fin-value text-success" id="budgetRemainingValue">₹0</div>
        </div>
      </div>
      <div class="fin-progress-wrap">
        <div class="fin-progress-labels">
          <span>Budget utilisation</span>
              <span class="text-danger" id="budgetUtilizationValue">0%</span>
        </div>
        <div class="progress-track">
              <div class="progress-fill" id="budgetUtilizationFill" style="width:0%"></div>
        </div>
      </div>
    </div>

    <!-- Project Summary (JS-driven) -->
    <div class="panel-card project-summary-card">
      <div class="panel-head">
        <h3><i class="bi bi-layers"></i> Project Summary</h3>
        <div id="projectSummaryPhase" class="phase-pill"></div>
      </div>
      <div class="ps-title" id="projectSummaryTitle">Loading…</div>
      <div class="ps-desc" id="projectSummaryDescription"></div>
      <div class="ps-stats">
        <div class="ps-pct" id="projectSummaryProgress">0%</div>
        <div class="ps-target" id="projectSummaryTarget"></div>
      </div>
      <div class="progress-track mt-2">
        <div class="progress-fill-gold" id="projectSummaryFill" style="width:0%"></div>
      </div>
    </div>

  </section>

  <!-- ── ALERTS + SITE STATUS ─────────── -->
  <section class="dual-grid">

    <div class="panel-card">
      <div class="panel-head">
        <h3><i class="bi bi-bell"></i> Alerts / Attention</h3>
        <span class="head-badge badge-amber">Review</span>
      </div>
      <div id="alertsList" class="alerts-list"></div>
    </div>

    <div class="panel-card">
      <div class="panel-head">
        <h3><i class="bi bi-pin-map"></i> Site-wise Status</h3>
      </div>
      <div id="projectStatusList" class="status-list"></div>
    </div>

  </section>

  <!-- ── BOTTOM 3 PANELS ──────────────── -->
  <section class="tri-grid">

    <div class="panel-card stack-panel">
      <div class="panel-head">
        <h3><i class="bi bi-bar-chart-steps"></i> Progress Overview</h3>
      </div>
      <div id="progressCardStack" class="stack-list"></div>
    </div>

    <div class="panel-card">
      <div class="panel-head">
        <h3><i class="bi bi-people"></i> Labour Summary</h3>
      </div>
      <div id="labourSummaryBox" class="stack-list"></div>
    </div>

    <div class="panel-card">
      <div class="panel-head">
        <h3><i class="bi bi-box-seam"></i> Material Status</h3>
      </div>
      <div id="materialStatusBox" class="stack-list"></div>
    </div>

  </section>

  <!-- ── RECENT ACTIVITY ───────────────── -->
  <section class="panel-card activity-panel">
    <div class="panel-head">
      <h3><i class="bi bi-activity"></i> Recent Activity</h3>
      <span class="head-badge">Timeline</span>
    </div>
    <div id="recentActivityList" class="activity-list"></div>
  </section>

  <!-- ── FOOTER ──────────────────────── -->
  <footer class="dash-footer">
    <div class="footer-left">
      <i class="bi bi-clock-history"></i>
       <span id="lastUpdated">Last Updated: -</span>
    </div>
    <div class="footer-right">
      <span class="sync-dot"></span>
      <span id="syncStatus">Data synced just now</span>
    </div>
  </footer>

</div><!-- /.dashboard-container -->
</div><!-- /.main -->


<!-- ══════════════════════════════════════
     HIDDEN JS STACKS (populated by JS,
     not shown in main UI — prevents
     console.warn from dashboard.js)
══════════════════════════════════════ -->
<div id="workersCardStack"   style="display:none"></div>
<div id="expenseCardStack"   style="display:none"></div>
<div id="materialsCardStack" style="display:none"></div>

<script src="js/sidebar.js?v=6"></script>
<script src="js/dashboard.js?v=6"></script>
</body>
</html>
