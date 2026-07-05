<?php
include "db.php";

$projects = [];
$incomeCategories = [];
$expenseCategories = [];

$projectResult = $conn->query("SELECT id, title FROM projects ORDER BY title ASC");
if ($projectResult) {
    while ($row = $projectResult->fetch_assoc()) {
        $projects[] = $row;
    }
}

$incomeResult = $conn->query("SELECT name FROM income_categories ORDER BY name ASC");
if ($incomeResult) {
    while ($row = $incomeResult->fetch_assoc()) {
        $incomeCategories[] = $row['name'];
    }
}

$expenseResult = $conn->query("SELECT name FROM expense_categories ORDER BY name ASC");
if ($expenseResult) {
    while ($row = $expenseResult->fetch_assoc()) {
        $expenseCategories[] = $row['name'];
    }
}

if (!$incomeCategories) {
    $incomeCategories = ["Client Payment", "Advance Received", "Material Return", "Other Income"];
}

if (!$expenseCategories) {
    $expenseCategories = ["Labour", "Material", "Transport", "Equipment", "Site Expense", "Others"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Income & Expense</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="css/sidebar.css?v=6">
<link rel="stylesheet" href="css/jgc-theme.css?v=6">
<link rel="stylesheet" href="css/income_expense.css?v=6">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main income-expense-main">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h1 class="page-title">Income & Expense</h1>
          <p class="page-sub">Track all income and expenses of your construction projects</p>
        </div>
      </div>

      <div class="topbar-right income-topbar-filters">
        <div class="filter-chip filter-chip-date">
          <label>Date Range</label>
          <div class="date-range-fields">
            <input type="date" id="filterFromDate" class="form-control form-control-sm">
            <span>to</span>
            <input type="date" id="filterToDate" class="form-control form-control-sm">
          </div>
        </div>

        <div class="filter-chip filter-chip-project">
          <label>Project</label>
          <select id="filterProject" class="form-select form-select-sm"></select>
        </div>

        <div class="split-action-group" id="entryActionGroup">
          <button class="tbtn tbtn-primary split-action-main" id="addEntryBtn" type="button">
            <i class="bi bi-plus-lg"></i>
            <span>Add New Entry</span>
          </button>

          <button class="tbtn tbtn-primary split-action-toggle" id="entryActionToggle" type="button" aria-haspopup="menu" aria-expanded="false" aria-label="Open income and expense actions">
            <i class="bi bi-chevron-down"></i>
          </button>

          <div class="split-action-menu" id="entryActionMenu" role="menu" aria-hidden="true">
            <button class="split-action-item" type="button" data-menu-action="add">
              <i class="bi bi-plus-lg"></i>
              <span>Add New Entry</span>
            </button>
            <button class="split-action-item" type="button" data-menu-action="upload">
              <i class="bi bi-upload"></i>
              <span>Upload Excel</span>
            </button>
            <button class="split-action-item" type="button" data-menu-action="report">
              <i class="bi bi-file-earmark-pdf"></i>
              <span>Download Report</span>
            </button>
          </div>
        </div>
        
        <div id="currentDate" class="live-date"></div>
      </div>
    </header>

  <div class="income-page-shell">

    <div class="summary-grid" id="summaryCards"></div>

    <div class="content-grid income-expense-layout">
      <section class="panel form-panel">
        <div class="panel-head">
          <p class="panel-kicker">Add Income / Expense</p>
          <h3>Record a new transaction</h3>
        </div>

        <div class="entry-tabs" role="tablist" aria-label="Income or Expense">
          <button class="entry-tab active" type="button" data-type="income">Income</button>
          <button class="entry-tab" type="button" data-type="expense">Expense</button>
        </div>

        <form id="entryForm" class="entry-form" enctype="multipart/form-data">
          <input type="hidden" id="entryId" name="entry_id" value="">
          <input type="hidden" id="entryType" name="type" value="income">

          <div class="form-grid two-col">
            <div class="field-group">
              <label for="entryProject">Project / Site</label>
              <select id="entryProject" name="project_id" class="form-select"></select>
            </div>

            <div class="field-group">
              <label for="entryCategory">Type / Category</label>
              <select id="entryCategory" name="category" class="form-select"></select>
            </div>
          </div>

          <div class="form-grid two-col">
            <div class="field-group">
              <label for="entryDate">Date</label>
              <input id="entryDate" name="entry_date" type="date" class="form-control">
            </div>

            <div class="field-group">
              <label for="entryAmount">Amount (₹)</label>
              <input id="entryAmount" name="amount" type="number" min="0" step="0.01" class="form-control" placeholder="Enter amount">
            </div>
          </div>

          <div class="form-grid two-col">
            <div class="field-group">
              <label for="entryPaymentMethod">Payment Method</label>
              <select id="entryPaymentMethod" name="payment_method" class="form-select">
                <option value="">Select method</option>
                <option value="Cash">Cash</option>
                <option value="Bank">Bank</option>
                <option value="UPI">UPI</option>
                <option value="Card">Card</option>
                <option value="Cheque">Cheque</option>
              </select>
            </div>

            <div class="field-group">
              <label id="partyLabel" for="entryPartyName">Received From</label>
              <input id="entryPartyName" name="party_name" type="text" class="form-control" placeholder="Enter name">
            </div>
          </div>

          <div class="field-group">
            <label for="entryDescription">Description / Notes</label>
            <textarea id="entryDescription" name="description" class="form-control" rows="4" placeholder="Enter description (optional)"></textarea>
          </div>

          <div class="field-group">
            <label for="entryReceipt">Upload Bill / Receipt (Optional)</label>
            <input id="entryReceipt" name="receipt" type="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
            <small class="field-hint">JPG, PNG, or PDF up to 5MB.</small>
          </div>

          <div class="form-actions">
            <button class="btn btn-light" type="reset" id="resetEntryBtn">Reset</button>
            <button class="btn btn-gold" type="submit" id="saveEntryBtn">Save Entry</button>
          </div>
        </form>
      </section>

      <section class="panel table-panel transaction-section">
        <div class="panel-head panel-head-row">
          <div>
            <p class="panel-kicker">All Income & Expense</p>
            <h3>Transaction ledger</h3>
          </div>
          <div class="table-tools">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="search" id="transactionSearch" class="form-control" placeholder="Search transactions...">
            </div>
            <button class="icon-btn" type="button" id="clearFiltersBtn" title="Clear filters">
              <i class="bi bi-funnel"></i>
            </button>
          </div>
        </div>

        <div class="transaction-ledger">
          <table class="transaction-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Type</th>
                <th>Category</th>
                <th>Project</th>
                <th>Description</th>
                <th>Party</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="transactionTableBody"></tbody>
          </table>
        </div>

        <div class="table-footer">
          <div class="table-meta" id="tableMeta"></div>
          <div class="pagination-wrap" id="paginationWrap"></div>
        </div>
      </section>
    </div>
  </div>
</div>

<div id="viewModal" class="view-modal" aria-hidden="true">
  <div class="view-modal-box">
    <button class="close-btn" type="button" id="closeViewModalBtn">×</button>
    <p class="panel-kicker">Transaction Details</p>
    <h3 id="viewModalTitle">Income & Expense</h3>
    <div id="viewModalBody" class="view-modal-body"></div>
  </div>
</div>

<script>
window.JGC_PROJECTS = <?php echo json_encode($projects, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.JGC_INCOME_CATEGORIES = <?php echo json_encode(array_values($incomeCategories), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.JGC_EXPENSE_CATEGORIES = <?php echo json_encode(array_values($expenseCategories), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<script src="js/sidebar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/script.js?v=4"></script>
<script src="js/income_expense.js?v=4"></script>
<input id="excelImportInput" type="file" accept=".xlsx,.csv" hidden>
</body>
</html>
