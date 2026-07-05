<?php
include "db.php";
mysqli_report(MYSQLI_REPORT_OFF);

function h($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function bindParams(mysqli_stmt $stmt, string $types, array $params): void {
  if ($types === '' || !$params) {
    return;
  }

  $bind = [$types];
  foreach ($params as $index => $value) {
    $bind[] = &$params[$index];
  }

  call_user_func_array([$stmt, 'bind_param'], $bind);
}

function preparedRow(mysqli $conn, string $sql, string $types = '', array $params = []): ?array {
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return null;
  }

  bindParams($stmt, $types, $params);

  if (!$stmt->execute()) {
    $stmt->close();
    return null;
  }

  $result = $stmt->get_result();
  $row = $result ? $result->fetch_assoc() : null;
  $stmt->close();

  return $row ?: null;
}

function preparedScalar(mysqli $conn, string $sql, string $types = '', array $params = [], $default = 0) {
  $row = preparedRow($conn, $sql, $types, $params);
  if (!$row) {
    return $default;
  }

  return array_values($row)[0] ?? $default;
}

$project_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$project = null;
$lastUpdated = '';
$status = 'Active';
$progress = 0;
$budget = 0;
$expense = 0;
$remainingBudget = 0;
$workerCount = 0;
$materialCount = 0;
$durationDays = 0;
$daysRemaining = 0;
$projectCode = '-';
$projectImageUrl = 'https://via.placeholder.com/1200x800?text=Project';

if ($project_id > 0) {
  $project = preparedRow(
    $conn,
    'SELECT id, title, city, address, start_date, end_date, image, created_at FROM projects WHERE id = ? LIMIT 1',
    'i',
    [$project_id]
  );

  if ($project) {
    $latestReport = preparedRow(
      $conn,
      'SELECT created_at FROM reports WHERE project_id = ? ORDER BY created_at DESC, id DESC LIMIT 1',
      'i',
      [$project_id]
    );

    $workerCount = (int) preparedScalar($conn, 'SELECT COUNT(*) FROM workers WHERE project_id = ?', 'i', [$project_id], 0);
    $materialCount = (int) preparedScalar($conn, 'SELECT COUNT(*) FROM materials', '', [], 0);
    $budget = (float) preparedScalar($conn, "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE project_id = ? AND type = 'income'", 'i', [$project_id], 0);
    $expense = (float) preparedScalar($conn, "SELECT COALESCE(SUM(amount), 0) FROM income_expense WHERE project_id = ? AND type = 'expense'", 'i', [$project_id], 0);

    $startTime = !empty($project['start_date']) ? strtotime($project['start_date']) : false;
    $endTime = !empty($project['end_date']) ? strtotime($project['end_date']) : false;
    $now = time();

    if ($startTime && $endTime && $endTime > $startTime) {
      $durationDays = (int) floor(($endTime - $startTime) / 86400) + 1;
      $elapsed = max(0, min($endTime - $startTime, $now - $startTime));
      $progress = (int) round(($elapsed / ($endTime - $startTime)) * 100);
      $daysRemaining = (int) floor(($endTime - $now) / 86400);
    }

    if ($endTime && $now > $endTime) {
      $status = 'Completed';
    }

    $remainingBudget = max($budget - $expense, 0);
    $projectCode = 'PRJ' . str_pad((string) $project['id'], 4, '0', STR_PAD_LEFT);
    $projectImageUrl = !empty($project['image']) ? $project['image'] : $projectImageUrl;
    $lastUpdated = $latestReport['created_at'] ?? $project['created_at'] ?? '';
  }
}

$projectTitle = $project['title'] ?? 'Project not found';
$projectLocation = $project['city'] ?? '-';
$projectStart = $project['start_date'] ?? '-';
$projectEnd = $project['end_date'] ?? '-';
$projectAddress = $project['address'] ?? '-';
$lastUpdatedText = $lastUpdated !== '' ? date('d M Y, h:i A', strtotime($lastUpdated)) : '-';
$progress = max(0, min(100, $progress));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Project Dashboard - <?php echo h($projectTitle); ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <link rel="stylesheet" href="css/sidebar.css?v=6">
  <link rel="stylesheet" href="css/jgc-theme.css?v=6">
  <link rel="stylesheet" href="css/project_details.css?v=6">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main project-details-page">
  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <h1 class="page-title">Project Dashboard</h1>
        <p class="page-sub" id="projectBreadcrumb"><?php echo $project ? h($projectTitle . ' · ' . $projectCode) : 'Project not found'; ?></p>
      </div>
    </div>

    <div class="topbar-right detail-actions">
      <a href="projects.php" class="tbtn tbtn-light detail-back-btn"><i class="bi bi-arrow-left"></i> Back</a>
      
      <!-- Actions Dropdown -->
      <div class="dropdown d-inline-block">
        <button class="tbtn tbtn-primary dropdown-toggle" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-lightning-charge-fill"></i> Actions
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionsDropdown">
          <li><button class="dropdown-item" type="button" id="scrollEditBtn"><i class="bi bi-pencil-square me-2"></i> Edit Project</button></li>
          <li><button class="dropdown-item" type="button" id="downloadSummaryBtn"><i class="bi bi-file-earmark-pdf me-2"></i> Download PDF Report</button></li>
          <li><button class="dropdown-item" type="button" id="reloadProjectBtn"><i class="bi bi-arrow-clockwise me-2"></i> Refresh Data</button></li>
        </ul>
      </div>

      <div id="currentDate" class="live-date"></div>
    </div>
  </header>

  <?php if (!$project): ?>
    <div class="alert alert-danger m-3">Project not found or invalid project ID specified.</div>
  <?php else: ?>
    <!-- 1. PROJECT HEADER CARD -->
    <div class="detail-header-card">
      <div class="header-left">
        <div class="header-image-wrap">
          <img id="projectHeroImage" src="<?php echo h($projectImageUrl); ?>" alt="Project image">
        </div>
        <div class="header-main-info">
          <span class="status-badge active" id="projectStatusPill"><?php echo h($status); ?></span>
          <h2 id="projectTitle"><?php echo h($projectTitle); ?></h2>
          <span class="client-name" id="projectClient"><i class="bi bi-building"></i> JGC Client / ABC Builders</span>
          <span class="location" id="projectLocation"><i class="bi bi-geo-alt"></i> <?php echo h($projectLocation); ?>, Tamil Nadu</span>
        </div>
      </div>

      <div class="header-meta-grid">
        <!-- Project Manager (Fallback / Loaded via JS) -->
        <div class="meta-item manager-meta">
          <div class="meta-icon-circle"><i class="bi bi-person-badge-fill"></i></div>
          <div class="meta-text">
            <small>Project Manager</small>
            <strong id="projectManagerName">Arun Kumar</strong>
            <span id="projectManagerPhone">+91 98765 43210</span>
          </div>
        </div>

        <!-- Start Date -->
        <div class="meta-item">
          <div class="meta-icon-circle"><i class="bi bi-calendar-event"></i></div>
          <div class="meta-text">
            <small>Start Date</small>
            <strong id="projectStart"><?php echo h($projectStart); ?></strong>
          </div>
        </div>

        <!-- Expected End Date -->
        <div class="meta-item">
          <div class="meta-icon-circle"><i class="bi bi-calendar-check"></i></div>
          <div class="meta-text">
            <small>Expected End Date</small>
            <strong id="projectEnd"><?php echo h($projectEnd); ?></strong>
            <span class="meta-delay-alert" id="projectDelayAlert">
              <?php echo $daysRemaining < 0 ? 'Delayed by ' . abs($daysRemaining) . ' Days' : 'On Track'; ?>
            </span>
          </div>
        </div>

        <!-- Project Type -->
        <div class="meta-item">
          <div class="meta-icon-circle"><i class="bi bi-house-gear-fill"></i></div>
          <div class="meta-text">
            <small>Project Type</small>
            <strong id="projectType">Residential</strong>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. KPI SUMMARY CARDS -->
    <div class="detail-kpi-row">
      <!-- Project Value -->
      <div class="detail-kpi-card kpi-blue">
        <div class="kpi-card-content">
          <div class="kpi-icon"><i class="bi bi-currency-rupee"></i></div>
          <div class="kpi-info">
            <span class="kpi-title">Project Value</span>
            <span class="kpi-value" id="kpiProjectValue">₹ 0</span>
            <span class="kpi-sub" id="kpiProjectValueSub">100% Contract</span>
          </div>
        </div>
        <div class="kpi-wave-wrap">
          <svg viewBox="0 0 120 28" preserveAspectRatio="none"><path d="M0,15 C30,5 60,25 90,10 L120,20 L120,28 L0,28 Z" fill="rgba(2, 132, 199, 0.05)" stroke="rgba(2, 132, 199, 0.2)" stroke-width="1.5"></path></svg>
        </div>
      </div>

      <!-- Amount Received -->
      <div class="detail-kpi-card kpi-green">
        <div class="kpi-card-content">
          <div class="kpi-icon"><i class="bi bi-wallet2"></i></div>
          <div class="kpi-info">
            <span class="kpi-title">Amount Received</span>
            <span class="kpi-value" id="kpiReceived">₹ 0</span>
            <span class="kpi-sub" id="kpiReceivedSub">0% of Value</span>
          </div>
        </div>
        <div class="kpi-wave-wrap">
          <svg viewBox="0 0 120 28" preserveAspectRatio="none"><path d="M0,18 C30,10 60,20 90,5 L120,15 L120,28 L0,28 Z" fill="rgba(22, 163, 74, 0.05)" stroke="rgba(22, 163, 74, 0.2)" stroke-width="1.5"></path></svg>
        </div>
      </div>

      <!-- Amount Pending -->
      <div class="detail-kpi-card kpi-orange">
        <div class="kpi-card-content">
          <div class="kpi-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="kpi-info">
            <span class="kpi-title">Amount Pending</span>
            <span class="kpi-value" id="kpiPending">₹ 0</span>
            <span class="kpi-sub" id="kpiPendingSub">0% Outstanding</span>
          </div>
        </div>
        <div class="kpi-wave-wrap">
          <svg viewBox="0 0 120 28" preserveAspectRatio="none"><path d="M0,20 C35,10 70,25 105,15 L120,18 L120,28 L0,28 Z" fill="rgba(234, 88, 12, 0.05)" stroke="rgba(234, 88, 12, 0.2)" stroke-width="1.5"></path></svg>
        </div>
      </div>

      <!-- Material Cost -->
      <div class="detail-kpi-card kpi-purple">
        <div class="kpi-card-content">
          <div class="kpi-icon"><i class="bi bi-boxes"></i></div>
          <div class="kpi-info">
            <span class="kpi-title">Material Cost</span>
            <span class="kpi-value" id="kpiMaterialCost">₹ 0</span>
            <span class="kpi-sub" id="kpiMaterialCostSub">0% of Spent</span>
          </div>
        </div>
        <div class="kpi-wave-wrap">
          <svg viewBox="0 0 120 28" preserveAspectRatio="none"><path d="M0,12 C30,22 60,8 90,18 L120,10 L120,28 L0,28 Z" fill="rgba(147, 51, 234, 0.05)" stroke="rgba(147, 51, 234, 0.2)" stroke-width="1.5"></path></svg>
        </div>
      </div>

      <!-- Labour Cost -->
      <div class="detail-kpi-card kpi-cyan">
        <div class="kpi-card-content">
          <div class="kpi-icon"><i class="bi bi-people-fill"></i></div>
          <div class="kpi-info">
            <span class="kpi-title">Labour Cost</span>
            <span class="kpi-value" id="kpiLabourCost">₹ 0</span>
            <span class="kpi-sub" id="kpiLabourCostSub">0% of Spent</span>
          </div>
        </div>
        <div class="kpi-wave-wrap">
          <svg viewBox="0 0 120 28" preserveAspectRatio="none"><path d="M0,15 C30,25 60,5 90,20 L120,12 L120,28 L0,28 Z" fill="rgba(6, 182, 212, 0.05)" stroke="rgba(6, 182, 212, 0.2)" stroke-width="1.5"></path></svg>
        </div>
      </div>

      <!-- Profit Margin -->
      <div class="detail-kpi-card kpi-teal">
        <div class="kpi-card-content">
          <div class="kpi-icon"><i class="bi bi-graph-up-arrow"></i></div>
          <div class="kpi-info">
            <span class="kpi-title">Profit Margin</span>
            <span class="kpi-value" id="kpiProfitMargin">₹ 0</span>
            <span class="kpi-sub" id="kpiProfitMarginSub">0% Margin</span>
          </div>
        </div>
        <div class="kpi-wave-wrap">
          <svg viewBox="0 0 120 28" preserveAspectRatio="none"><path d="M0,10 C30,5 60,20 90,15 L120,8 L120,28 L0,28 Z" fill="rgba(20, 184, 166, 0.05)" stroke="rgba(20, 184, 166, 0.2)" stroke-width="1.5"></path></svg>
        </div>
      </div>

      <!-- Work Progress -->
      <div class="detail-kpi-card kpi-gold">
        <div class="kpi-card-content">
          <div class="kpi-icon"><i class="bi bi-percent"></i></div>
          <div class="kpi-info">
            <span class="kpi-title">Work Progress</span>
            <span class="kpi-value" id="kpiProgress"><?php echo (int)$progress; ?>%</span>
            <div class="kpi-progress-bar-wrap">
              <div class="kpi-progress-bar-fill" id="kpiProgressFill" style="width: <?php echo (int)$progress; ?>%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MIDDLE ROW PANELS -->
    <div class="dashboard-middle-row">
      <!-- 3. Work Breakdown Structure (WBS) -->
      <div class="dashboard-card wbs-panel">
        <div class="card-title-bar">
          <h3>Work Breakdown Structure (WBS)</h3>
        </div>
        <div class="wbs-tree" id="wbsTreeContainer">
          <!-- Populated by JS -->
        </div>
      </div>

      <!-- 4. Project Schedule (Gantt Chart) -->
      <div class="dashboard-card gantt-panel">
        <div class="card-title-bar">
          <h3>Project Schedule (Gantt Chart)</h3>
        </div>
        <div class="gantt-timeline-container">
          <div class="gantt-header-row" id="ganttHeaderRow">
            <!-- Months dynamically drawn by JS -->
          </div>
          <div class="gantt-body" id="ganttBodyContainer">
            <!-- Rows dynamically drawn by JS -->
          </div>
        </div>
        <div class="gantt-legend">
          <span><span class="legend-bullet completed"></span> Completed</span>
          <span><span class="legend-bullet ongoing"></span> In Progress</span>
          <span><span class="legend-bullet upcoming"></span> Upcoming</span>
          <span><span class="legend-bullet pending"></span> Pending</span>
        </div>
      </div>

      <!-- Right column panels -->
      <div class="dashboard-right-col">
        <!-- 5. Project Completion Chart -->
        <div class="dashboard-card completion-panel">
          <div class="card-title-bar">
            <h3>Project Completion</h3>
          </div>
          <div class="circular-progress-wrap">
            <div class="circular-progress" id="completionCircularProgress" style="background: conic-gradient(var(--primary-gold) <?php echo $progress * 3.6; ?>deg, #e2e8f0 0deg)">
              <div class="circular-progress-inner">
                <strong id="circularProgressText"><?php echo (int)$progress; ?>%</strong>
                <span>Completed</span>
              </div>
            </div>
          </div>
          <div class="completion-legend-grid">
            <div class="legend-item"><span class="bullet completed"></span> <span>Completed</span> <strong id="legendCompletedVal"><?php echo (int)$progress; ?>%</strong></div>
            <div class="legend-item"><span class="bullet ongoing"></span> <span>In Progress</span> <strong id="legendOngoingVal">0%</strong></div>
            <div class="legend-item"><span class="bullet pending"></span> <span>Pending</span> <strong id="legendPendingVal">0%</strong></div>
            <div class="legend-item"><span class="bullet not-started"></span> <span>Not Started</span> <strong id="legendNotStartedVal">0%</strong></div>
          </div>
        </div>

        <!-- 6. Upcoming Milestones -->
        <div class="dashboard-card milestones-panel">
          <div class="card-title-bar">
            <h3>Upcoming Milestones</h3>
            <a href="reports.php" class="view-all-link">View All</a>
          </div>
          <div class="milestones-list" id="upcomingMilestonesContainer">
            <!-- Populated dynamically by JS -->
          </div>
        </div>
      </div>
    </div>

    <!-- BOTTOM ROW PANELS -->
    <div class="dashboard-bottom-row">
      <!-- 7. Project Profitability -->
      <div class="dashboard-card profitability-panel">
        <div class="card-title-bar">
          <h3>Project Profitability (Live)</h3>
        </div>
        <div class="profitability-content">
          <div class="profitability-chart-wrap">
            <div class="donut-chart" id="profitabilityDonutChart">
              <div class="donut-chart-inner">
                <small>Net Profit</small>
                <strong id="profitabilityNetValue">₹ 0</strong>
              </div>
            </div>
          </div>
          <div class="profitability-details-list">
            <div class="profit-row"><span>Contract Value</span><strong id="profContractValue">₹ 0</strong></div>
            <div class="profit-row"><span class="color-dot mat"></span><span>Material Cost</span><strong id="profMaterialCost">₹ 0</strong></div>
            <div class="profit-row"><span class="color-dot lab"></span><span>Labour Cost</span><strong id="profLabourCost">₹ 0</strong></div>
            <div class="profit-row"><span class="color-dot oth"></span><span>Other Expenses</span><strong id="profOtherCost">₹ 0</strong></div>
            <hr class="my-1">
            <div class="profit-row"><span>Total Expenses</span><strong id="profTotalExpense">₹ 0</strong></div>
            <div class="profit-row font-weight-bold"><span>Received Amount</span><strong id="profReceived">₹ 0</strong></div>
            <div class="profit-row net-profit-row"><span>Net Profit</span><strong id="profNetProfit">₹ 0</strong></div>
            <div class="profit-row profit-percent-row"><span>Profit %</span><strong id="profProfitPercent">0%</strong></div>
          </div>
        </div>
      </div>

      <!-- 8. Recent Daily Reports -->
      <div class="dashboard-card reports-panel">
        <div class="card-title-bar">
          <h3>Recent Daily Reports</h3>
          <a href="daily.php" class="view-all-link">View All</a>
        </div>
        <div class="reports-list" id="dailyReportsContainer">
          <!-- Populated by JS -->
        </div>
      </div>

      <!-- 9. Recent Site Photos -->
      <div class="dashboard-card photos-panel">
        <div class="card-title-bar">
          <h3>Recent Site Photos</h3>
          <a href="projects.php" class="view-all-link">View All</a>
        </div>
        <div class="photos-grid" id="sitePhotosContainer">
          <!-- Populated by JS -->
        </div>
      </div>

      <!-- 10. Open Issues / RFI -->
      <div class="dashboard-card issues-panel">
        <div class="card-title-bar">
          <h3>Open Issues / RFI</h3>
          <a href="daily.php" class="view-all-link">View All</a>
        </div>
        <div class="issues-list" id="openIssuesContainer">
          <!-- Populated by JS -->
        </div>
      </div>
    </div>

    <!-- Hidden elements required by legacy JS compatibility -->
    <div style="display:none;">
      <span id="projectTitleTop"></span>
      <span id="siteStatus"></span>
      <span id="projectStatusText"></span>
      <span id="summaryProjectCode"></span>
      <span id="summaryTitle"></span>
      <span id="summaryLocation"></span>
      <span id="summaryAddress"></span>
      <span id="summaryDaysRemaining"></span>
      <span id="budgetTotal"></span>
      <span id="budgetSpent"></span>
      <span id="budgetRemaining"></span>
      <span id="budgetVariance"></span>
      <span id="plannedProgress"></span>
      <span id="actualProgress"></span>
      <span id="delayDays"></span>
      <div id="plannedProgressFill"></div>
      <div id="actualProgressFill"></div>
      <span id="siteStart"></span>
      <span id="siteEnd"></span>
      <span id="projectDuration"></span>
      <span id="projectProgressText"></span>
      <div id="projectProgressFill"></div>
      <span id="metricWorkers"></span>
      <span id="metricMaterials"></span>
      <span id="metricBudget"></span>
      <span id="metricExpense"></span>
      <div id="milestonesList"></div>
      <div id="workStatusList"></div>
      <div id="risksList"></div>
      <div id="notesList"></div>
      <div id="qualityList"></div>
      <div id="safetyList"></div>
      <div id="attachmentsList"></div>
      <div id="workersList"></div>
      <div id="materialsList"></div>
    </div>

    <!-- EXISTING EDIT PROJECT SECTION -->
    <section class="edit-section card-surface" id="editSection" style="margin-top: 24px;">
      <div class="card-heading align-start">
        <div>
          <span class="section-label">Edit Project</span>
          <h3 class="mb-1">Update project information</h3>
        </div>
        <button type="button" class="btn btn-outline-secondary" id="reloadProjectBtn"><i class="bi bi-arrow-clockwise"></i> Reset</button>
      </div>

      <form id="projectEditForm" class="edit-form">
        <input type="hidden" id="projectIdField">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Project Name</label>
            <input type="text" id="editTitle" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">City</label>
            <input type="text" id="editCity" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <input type="text" id="editAddress" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Start Date</label>
            <input type="date" id="editStart" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">End Date</label>
            <input type="date" id="editEnd" class="form-control">
          </div>
          <div class="col-12">
            <label class="form-label">Project Image</label>
            <input type="file" id="editImage" class="form-control" accept="image/png, image/jpeg">
            <small class="form-hint">Leave blank to keep the current image.</small>
          </div>
        </div>

        <div class="edit-actions">
          <button type="button" class="btn btn-light" id="cancelEditBtn">Cancel</button>
          <button type="submit" class="btn btn-warning"><i class="bi bi-check2-circle"></i> Save Changes</button>
        </div>
      </form>
    </section>
  <?php endif; ?>
</div>

<footer class="dash-footer">
  <div class="footer-left">
    <i class="bi bi-clock-history"></i>
    <span id="lastUpdated">Last Updated: <?php echo h($lastUpdatedText); ?></span>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/sidebar.js"></script>
<script src="js/script.js?v=6"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="js/project_details.js?v=6"></script>
</body>
</html>