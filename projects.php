<?php
include "db.php";

$now_time = time();
$today_str = date('Y-m-d');

$totalProjects = 0;
$ongoingProjects = 0;
$completedProjects = 0;
$onHoldProjects = 0;
$delayedProjects = 0;
$totalValue = 0;

$res = $conn->query("SELECT * FROM projects");
if ($res) {
    while ($p = $res->fetch_assoc()) {
        $totalProjects++;
        
        // Calculate progress (time-based fallback like dashboard.php)
        $startTime = !empty($p['start_date']) ? strtotime($p['start_date']) : false;
        $endTime = !empty($p['end_date']) ? strtotime($p['end_date']) : false;
        
        $progress = 0;
        if ($startTime && $endTime && $endTime > $startTime) {
            $duration = $endTime - $startTime;
            $elapsed = max(0, min($duration, $now_time - $startTime));
            $progress = (int) round(($elapsed / $duration) * 100);
        }
        $progress = max(0, min(100, $progress));
        
        // If there's a progress in reports, override it (like get_project.php does)
        $projId = (int)$p['id'];
        $reportRes = $conn->query("SELECT progress, budget FROM reports WHERE project_id = $projId LIMIT 1");
        $projectValue = 0;
        if ($reportRes && $reportRes->num_rows > 0) {
            $rRow = $reportRes->fetch_assoc();
            if (isset($rRow['progress'])) {
                $progress = (int)$rRow['progress'];
            }
            $projectValue = (float)($rRow['budget'] ?? 0);
        }
        
        // Fallback for budget if not found or 0 in reports
        if ($projectValue <= 0) {
            $incomeRes = $conn->query("SELECT SUM(amount) AS total_income FROM income_expense WHERE project_id = $projId AND type = 'income'");
            if ($incomeRes) {
                $iRow = $incomeRes->fetch_assoc();
                $projectValue = (float)($iRow['total_income'] ?? 0);
            }
        }
        $totalValue += $projectValue;

        // Determine status category
        if ($endTime && $now_time > $endTime && $progress < 100) {
            $delayedProjects++;
        } elseif ($progress >= 100 || ($endTime && $now_time > $endTime)) {
            $completedProjects++;
        } elseif ($startTime && $now_time < $startTime) {
            $onHoldProjects++;
        } else {
            $ongoingProjects++;
        }
    }
}
// TODO: Connect direct query for total value if database is updated to store budget in projects table.
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Project Locations</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Your CSS -->
  <link rel="stylesheet" href="css/sidebar.css?v=6">
  <link rel="stylesheet" href="css/jgc-theme.css?v=6">
  <link rel="stylesheet" href="css/projects.css?v=6">
</head>

<body>

<!-- 🔹 SIDEBAR -->
<?php include 'sidebar.php'; ?>

<!-- 🔹 MAIN -->
<div class="main">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <h1 class="page-title">Projects</h1>
        <p class="page-sub">Manage all construction sites</p>
      </div>
    </div>

    <div class="topbar-right">
      <button class="tbtn tbtn-primary" onclick="openForm()"><i class="bi bi-plus-lg"></i> Add Project</button>
      <div id="currentDate" class="live-date"></div>
    </div>
  </header>

  <!-- 📊 KPI SUMMARY CARDS -->
  <?php
  function formatRupeeIndian($num) {
      $num = (int)$num;
      if (strlen($num) > 3) {
          $lastthree = substr($num, strlen($num) - 3, 3);
          $restunits = substr($num, 0, strlen($num) - 3);
          $restunits = (strlen($restunits) % 2 == 1) ? "0" . $restunits : $restunits;
          $expunit = str_split($restunits, 2);
          $explod = array();
          for ($i = 0; $i < count($expunit); $i++) {
              if ($i == 0) {
                  $explod[] = (int)$expunit[$i];
              } else {
                  $explod[] = $expunit[$i];
              }
          }
          $explod[] = $lastthree;
          return implode(",", $explod);
      }
      return $num;
  }
  ?>
  <div class="projects-kpi-row">
    <!-- Card 1: Total Projects -->
    <div class="projects-kpi-card kpi-blue">
      <div class="projects-kpi-card-content">
        <div class="projects-kpi-icon"><i class="bi bi-grid-fill"></i></div>
        <div class="projects-kpi-details">
          <span class="projects-kpi-title">Total Projects</span>
          <span class="projects-kpi-value"><?php echo $totalProjects; ?></span>
          <span class="projects-kpi-subtitle">All Time</span>
        </div>
      </div>
      <div class="projects-kpi-wave-wrap">
        <svg class="projects-kpi-wave" viewBox="0 0 120 28" preserveAspectRatio="none">
          <path d="M0,15 C30,5 60,25 90,10 L120,20 L120,28 L0,28 Z" fill="rgba(2, 132, 199, 0.05)" stroke="rgba(2, 132, 199, 0.2)" stroke-width="1.5"></path>
        </svg>
      </div>
    </div>

    <!-- Card 2: Ongoing -->
    <div class="projects-kpi-card kpi-green">
      <div class="projects-kpi-card-content">
        <div class="projects-kpi-icon"><i class="bi bi-play-circle-fill"></i></div>
        <div class="projects-kpi-details">
          <span class="projects-kpi-title">Ongoing</span>
          <span class="projects-kpi-value"><?php echo $ongoingProjects; ?></span>
          <span class="projects-kpi-subtitle">This Month</span>
        </div>
      </div>
      <div class="projects-kpi-wave-wrap">
        <svg class="projects-kpi-wave" viewBox="0 0 120 28" preserveAspectRatio="none">
          <path d="M0,18 C30,10 60,20 90,5 L120,15 L120,28 L0,28 Z" fill="rgba(22, 163, 74, 0.05)" stroke="rgba(22, 163, 74, 0.2)" stroke-width="1.5"></path>
        </svg>
      </div>
    </div>

    <!-- Card 3: Completed -->
    <div class="projects-kpi-card kpi-purple">
      <div class="projects-kpi-card-content">
        <div class="projects-kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="projects-kpi-details">
          <span class="projects-kpi-title">Completed</span>
          <span class="projects-kpi-value"><?php echo $completedProjects; ?></span>
          <span class="projects-kpi-subtitle">This Month</span>
        </div>
      </div>
      <div class="projects-kpi-wave-wrap">
        <svg class="projects-kpi-wave" viewBox="0 0 120 28" preserveAspectRatio="none">
          <path d="M0,12 C30,22 60,8 90,18 L120,10 L120,28 L0,28 Z" fill="rgba(147, 51, 234, 0.05)" stroke="rgba(147, 51, 234, 0.2)" stroke-width="1.5"></path>
        </svg>
      </div>
    </div>

    <!-- Card 4: On Hold -->
    <div class="projects-kpi-card kpi-orange">
      <div class="projects-kpi-card-content">
        <div class="projects-kpi-icon"><i class="bi bi-pause-circle-fill"></i></div>
        <div class="projects-kpi-details">
          <span class="projects-kpi-title">On Hold</span>
          <span class="projects-kpi-value"><?php echo $onHoldProjects; ?></span>
          <span class="projects-kpi-subtitle">Active</span>
        </div>
      </div>
      <div class="projects-kpi-wave-wrap">
        <svg class="projects-kpi-wave" viewBox="0 0 120 28" preserveAspectRatio="none">
          <path d="M0,20 C35,10 70,25 105,15 L120,18 L120,28 L0,28 Z" fill="rgba(234, 88, 12, 0.05)" stroke="rgba(234, 88, 12, 0.2)" stroke-width="1.5"></path>
        </svg>
      </div>
    </div>

    <!-- Card 5: Delayed -->
    <div class="projects-kpi-card kpi-red">
      <div class="projects-kpi-card-content">
        <div class="projects-kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div class="projects-kpi-details">
          <span class="projects-kpi-title">Delayed</span>
          <span class="projects-kpi-value"><?php echo $delayedProjects; ?></span>
          <span class="projects-kpi-subtitle">Require Attention</span>
        </div>
      </div>
      <div class="projects-kpi-wave-wrap">
        <svg class="projects-kpi-wave" viewBox="0 0 120 28" preserveAspectRatio="none">
          <path d="M0,15 C30,25 60,5 90,20 L120,12 L120,28 L0,28 Z" fill="rgba(220, 38, 38, 0.05)" stroke="rgba(220, 38, 38, 0.2)" stroke-width="1.5"></path>
        </svg>
      </div>
    </div>

    <!-- Card 6: Total Project Value -->
    <div class="projects-kpi-card kpi-gold">
      <div class="projects-kpi-card-content">
        <div class="projects-kpi-icon"><i class="bi bi-currency-rupee"></i></div>
        <div class="projects-kpi-details">
          <span class="projects-kpi-title">Total Value</span>
          <span class="projects-kpi-value">₹ <?php echo formatRupeeIndian($totalValue); ?></span>
          <span class="projects-kpi-subtitle">All Projects</span>
        </div>
      </div>
      <div class="projects-kpi-wave-wrap">
        <svg class="projects-kpi-wave" viewBox="0 0 120 28" preserveAspectRatio="none">
          <path d="M0,10 C30,5 60,20 90,15 L120,8 L120,28 L0,28 Z" fill="rgba(212, 175, 55, 0.05)" stroke="rgba(212, 175, 55, 0.2)" stroke-width="1.5"></path>
        </svg>
      </div>
    </div>
  </div>

  <div class="projects-shell">

    <div class="projects-toolbar">
      <div class="toolbar-left">
        <div class="field-icon-wrap search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" id="projectSearch" class="form-control" placeholder="Search projects...">
        </div>

        <select id="statusFilter" class="form-select">
          <option value="all">All Status</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
        </select>

        <select id="locationFilter" class="form-select">
          <option value="all">All Locations</option>
        </select>

        <select id="sortFilter" class="form-select">
          <option value="newest">Newest First</option>
          <option value="oldest">Oldest First</option>
          <option value="name_asc">Name A-Z</option>
          <option value="name_desc">Name Z-A</option>
        </select>
      </div>

      <div class="view-toggle" role="group" aria-label="View toggle">
        <button id="viewGridBtn" class="active" type="button" title="Grid view"><i class="bi bi-grid"></i></button>
        <button id="viewListBtn" type="button" title="List view"><i class="bi bi-list"></i></button>
      </div>
    </div>

    <!-- PROJECTS -->
    <div id="projectsContainer"></div>

    <div class="projects-footer">
      <small id="projectCountText">Showing 0 of 0 projects</small>
      <div class="pagination" id="projectsPagination"></div>
    </div>

  </div>

</div>

<!-- 🔥 ADD PROJECT MODAL -->
<div class="form-modal" id="formModal">
  <div class="form-box">

    <h5 id="formTitle" class="mb-3">Add Project</h5>

    <input type="text" id="title" placeholder="Project Name" class="form-control mb-2">

    <input type="text" id="city" placeholder="City" class="form-control mb-2">

    <input type="text" id="address" placeholder="Full Address" class="form-control mb-2">

    <label class="mb-1">Work Commencement</label>
    <input type="date" id="start" class="form-control mb-2">

    <label class="mb-1">Work Completion</label>
    <input type="date" id="end" class="form-control mb-2">

    <label class="mb-1">Upload Image (PNG / JPEG only)</label>
    <input type="file" id="image" accept="image/png, image/jpeg" class="form-control mb-3">

    <button class="btn btn-warning w-100 mb-2" onclick="saveProject()">Save Project</button>

    <button class="btn btn-light w-100" onclick="closeForm()">Cancel</button>

  </div>
</div>

<!-- 🔥 DETAILS MODAL (RECTANGLE AJAX STYLE) -->
<div class="details-modal" id="detailsModal">

  <div class="details-box">

    <span class="close-btn" onclick="closeDetails()">×</span>

    <div class="details-content">

      <!-- LEFT IMAGE -->
      <div class="details-left">
        <img id="detailImage">
      </div>

      <!-- RIGHT DETAILS -->
      <div class="details-right">
        <h4 id="detailTitle"></h4>

        <p><b>City:</b> <span id="detailCity"></span></p>
        <p><b>Address:</b> <span id="detailAddress"></span></p>
        <p><b>Workers:</b> <span id="detailWorkers"></span></p>
        <p><b>Start Date:</b> <span id="detailStart"></span></p>
        <p><b>End Date:</b> <span id="detailEnd"></span></p>
      </div>

    </div>

  </div>

</div>

<!-- 🔹 JS -->
<script src="js/sidebar.js"></script>
<script src="js/script1.js"></script>
<script src="js/script.js?v=4"></script>
<script src="js/projects.js?v=4"></script>

</body>
</html>