<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once 'auth.php';

// Optional: pre-fill lead_id from query string if coming from CRM
$prefill_lead_id = isset($_GET['lead_id']) ? intval($_GET['lead_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="New Quotation – JGC Constructions ERP. Create a new quotation with BOQ.">
  <title>New Quotation – JGC Constructions</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Project CSS -->
  <link rel="stylesheet" href="css/sidebar.css?v=6">
  <link rel="stylesheet" href="css/jgc-theme.css?v=6">
  <link rel="stylesheet" href="css/leads.css?v=6">
  <link rel="stylesheet" href="css/quotations.css?v=6">
  <link rel="stylesheet" href="css/new_quotation.css?v=6">
  <style>
    /* Styling adjustments for form rows in full page */
    .nq-form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 14px;
    }
    @media (max-width: 768px) {
      .nq-form-row {
        grid-template-columns: 1fr;
      }
    }
    .form-section-title {
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #8c763d;
      margin: 18px 0 10px;
      border-bottom: 1px solid rgba(15, 23, 42, 0.06);
      padding-bottom: 4px;
    }
    .boq-section-header {
      font-family: 'Inter', sans-serif;
    }
    .boq-grid-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    .boq-grid-table th {
      background: #f8fafc;
      padding: 8px 10px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: #64748b;
      border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .boq-grid-table td {
      padding: 6px;
      border-bottom: 1px solid rgba(15, 23, 42, 0.05);
      vertical-align: top;
    }
    .boq-input {
      width: 100%;
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: 6px;
      padding: 6px 10px;
      font-size: 13px;
      outline: none;
      background: #ffffff;
      transition: border-color 0.15s;
    }
    .boq-input:focus {
      border-color: #D4AF37;
    }
    .boq-row-del-btn {
      background: none;
      border: none;
      color: #dc2626;
      cursor: pointer;
      font-size: 14px;
      padding: 6px;
      border-radius: 6px;
      transition: background 0.15s;
    }
    .boq-row-del-btn:hover {
      background: #fee2e2;
    }
    .boq-summary-table {
      width: 100%;
      font-size: 13px;
    }
    .boq-summary-table td {
      padding: 6px 0;
    }
    .boq-summary-label {
      color: #64748b;
      font-weight: 500;
    }
    .boq-summary-value {
      text-align: right;
      font-weight: 700;
      color: #0f172a;
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<?php include 'sidebar.php'; ?>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </button>
        <!-- Breadcrumb -->
        <div>
          <nav class="nq-breadcrumb" aria-label="breadcrumb" style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500; font-family: 'Inter', sans-serif;">
            <a href="quotations.php" style="color: #64748b; text-decoration: none;"><i class="bi bi-file-earmark-spreadsheet"></i> Quotation</a>
            <i class="bi bi-chevron-right" style="font-size: 10px; color: #94a3b8;"></i>
            <span id="nqBreadcrumbLabel" style="color: #0f172a; font-weight: 700;">New Quotation</span>
          </nav>
        </div>
      </div>
      <div class="topbar-right">
        <button class="tbtn tbtn-ghost" id="btnCancelNQ" onclick="location.href='quotations.php'">
          <i class="bi bi-x-lg"></i> Cancel
        </button>
        <button class="tbtn tbtn-primary" id="btnSaveNQ">
          <i class="bi bi-check-lg"></i> <span id="btnSaveLabel">Save Quotation</span>
        </button>
        <div id="currentDate" class="live-date"></div>
      </div>
    </header>

  <div class="nq-shell">

    <form id="quotationForm" novalidate>
      <input type="hidden" id="qEditId" name="id" value="">

      <div class="nq-grid">
        <!-- Left Side: Inputs & BOQ -->
        <div class="nq-main-panel">
          
          <!-- Card 1: Client & Proposal Information -->
          <div class="nq-card">
            <div class="nq-card-header">
              <i class="bi bi-building"></i>
              <h5>Client &amp; Proposal Details</h5>
            </div>
            <div class="nq-card-body">
              <!-- CRM Lead link dropdown -->
              <div class="leads-field-group mb-3">
                <label for="linkLeadSelect" class="leads-label">Link CRM Lead (Optional)</label>
                <div class="leads-field-icon-wrap">
                  <i class="bi bi-person-lines-fill"></i>
                  <select id="linkLeadSelect" name="lead_id" class="leads-input">
                    <option value="">Select CRM Lead…</option>
                  </select>
                </div>
                <div class="form-text text-muted small mt-1">Linking a lead will auto-populate client fields.</div>
              </div>

              <div class="form-section-title">Client Information</div>
              <div class="nq-form-row">
                <div class="leads-field-group">
                  <label for="qClientName" class="leads-label">Contact Person <span class="req">*</span></label>
                  <div class="leads-field-icon-wrap">
                    <i class="bi bi-person"></i>
                    <input type="text" id="qClientName" name="client_name" class="leads-input" placeholder="e.g. Rajesh Kumar" required>
                  </div>
                </div>
                <div class="leads-field-group">
                  <label for="qClientCompany" class="leads-label">Company Name</label>
                  <div class="leads-field-icon-wrap">
                    <i class="bi bi-building"></i>
                    <input type="text" id="qClientCompany" name="client_company" class="leads-input" placeholder="e.g. ABC Developers">
                  </div>
                </div>
              </div>

              <div class="leads-field-group mb-3">
                <label for="qClientAddress" class="leads-label">Site / Billing Address</label>
                <div class="leads-field-icon-wrap">
                  <i class="bi bi-geo-alt"></i>
                  <textarea id="qClientAddress" name="client_address" class="leads-input leads-textarea" rows="2" placeholder="e.g. Plot 4, Tech Park, Bangalore"></textarea>
                </div>
              </div>

              <div class="form-section-title">Project details</div>
              <div class="nq-form-row">
                <div class="leads-field-group">
                  <label for="qTitle" class="leads-label">Project / Proposal Title <span class="req">*</span></label>
                  <div class="leads-field-icon-wrap">
                    <i class="bi bi-card-heading"></i>
                    <input type="text" id="qTitle" name="title" class="leads-input" placeholder="e.g. Villa Civil &amp; Plumbing BOQ" required>
                  </div>
                </div>
                <div class="leads-field-group" id="qStatusContainer" style="display: none;">
                  <label for="qStatus" class="leads-label">Quotation Status</label>
                  <div class="leads-field-icon-wrap">
                    <i class="bi bi-circle-half"></i>
                    <select id="qStatus" name="status" class="leads-input">
                      <option value="Draft">Draft</option>
                      <option value="Sent">Sent</option>
                      <option value="Approved">Approved</option>
                      <option value="Rejected">Rejected</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2: BOQ Container -->
          <div class="nq-card">
            <div class="nq-card-header d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-list-task"></i>
                <h5 class="m-0">Bill of Quantities (BOQ)</h5>
              </div>
              <button type="button" class="btn btn-sm text-white" id="btnAddCategory" style="background-color: #D4AF37; font-size:12px; font-weight:600;">
                <i class="bi bi-plus-lg me-1"></i> Add Category
              </button>
            </div>
            <div class="nq-card-body p-0" id="boqContainer">
              <!-- JS code will dynamically render category sections and rows here -->
            </div>
          </div>

        </div>

        <!-- Right Side: Sidebar Panel with Totals & Terms -->
        <div class="nq-sidebar-panel">

          <!-- Card 3: Estimate Summary -->
          <div class="nq-card nq-summary-card">
            <div class="nq-card-header">
              <i class="bi bi-calculator"></i>
              <h5>Estimate Summary</h5>
            </div>
            <div class="nq-card-body">
              <table class="boq-summary-table">
                <tr>
                  <td class="boq-summary-label">Subtotal</td>
                  <td class="boq-summary-value" id="calcSubtotal">₹0.00</td>
                </tr>
                <tr>
                  <td class="boq-summary-label">GST Rate (%)</td>
                  <td>
                    <input type="number" id="qTaxRate" name="tax_rate" class="boq-input text-end py-1" value="18.00" step="0.01" style="width: 80px; float: right; height: 28px; font-size: 12px;">
                  </td>
                </tr>
                <tr>
                  <td class="boq-summary-label">GST Tax Amount</td>
                  <td class="boq-summary-value" id="calcTaxAmount">₹0.00</td>
                </tr>
                <tr>
                  <td class="boq-summary-label">Discount (₹)</td>
                  <td>
                    <input type="number" id="qDiscount" name="discount" class="boq-input text-end py-1" value="0.00" step="0.01" style="width: 100px; float: right; height: 28px; font-size: 12px;">
                  </td>
                </tr>
                <tr class="nq-grand-row">
                  <td class="boq-summary-label" style="font-size: 14px; font-weight: 700; color: #B8860B;">Grand Total</td>
                  <td class="boq-summary-value" style="font-size: 14px; font-weight: 700; color: #B8860B;" id="calcGrandTotal">₹0.00</td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Card 4: Notes and Terms -->
          <div class="nq-card">
            <div class="nq-card-header">
              <i class="bi bi-file-earmark-text"></i>
              <h5>Notes &amp; Conditions</h5>
            </div>
            <div class="nq-card-body">
              <textarea id="qNotes" name="notes" class="leads-input leads-textarea" rows="4" placeholder="e.g. 1. Rates are inclusive of material transport. 2. Payments to be made in stages..." style="font-size: 12px;"></textarea>
            </div>
          </div>

          <!-- Card 5: Quick Help Guide -->
          <div class="nq-card">
            <div class="nq-card-header">
              <i class="bi bi-info-circle"></i>
              <h5>Quick Guide</h5>
            </div>
            <div class="nq-card-body">
              <ul class="nq-help-list">
                <li>
                  <i class="bi bi-info-circle-fill text-muted"></i>
                  <span>Select a Lead to pre-fill client details.</span>
                </li>
                <li>
                  <i class="bi bi-plus-circle-fill text-muted"></i>
                  <span>Add categories to structure estimate scope.</span>
                </li>
                <li>
                  <i class="bi bi-keyboard-fill text-muted"></i>
                  <span>Press <kbd>Ctrl</kbd> + <kbd>S</kbd> to save.</span>
                </li>
              </ul>
            </div>
          </div>

        </div>
      </div>
    </form>

  </div>
</div>

<!-- Dynamic data variables from PHP to JS -->
<script>
  var PREFILL_LEAD_ID = <?= $prefill_lead_id ?>;
  var EDIT_QUOTATION_ID = <?= isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0 ?>;
</script>

<!-- Scripts -->
<script src="js/sidebar.js?v=6"></script>
<script src="js/new_quotation.js?v=6"></script>
<script src="js/script.js?v=6"></script>
</body>
</html>