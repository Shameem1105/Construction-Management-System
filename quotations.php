<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Quotations & BOQ – JGC Constructions ERP. Manage estimates, bill of quantities, revisions, and project handoffs.">
  <title>Quotations &amp; BOQ – JGC Constructions</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Project CSS -->
  <link rel="stylesheet" href="css/sidebar.css?v=6">
  <link rel="stylesheet" href="css/jgc-theme.css?v=6">
  <link rel="stylesheet" href="css/leads.css?v=6">
  <link rel="stylesheet" href="css/quotations.css?v=6">
</head>
<body>

<!-- SIDEBAR -->
<?php include 'sidebar.php'; ?>

<!-- MAIN -->
<div class="main">

    
    <!-- TOPBAR (Redesigned ERP Style) -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h1 class="page-title">Quotation</h1>
          <p class="page-sub">Manage estimates, bill of quantities, and revisions</p>
        </div>
      </div>

      <div class="topbar-right">
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 280px;">
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0" style="padding: 6px 10px;"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="qSearchInput" class="form-control bg-light border-start-0 shadow-none" placeholder="Search..." style="font-size:13px; height:36px;">
          </div>
        </div>

        <div class="dropdown">
          <button class="tbtn tbtn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
            <i class="bi bi-download text-muted me-1"></i> Export
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size:14px;">
            <li><a class="dropdown-item py-2" href="#" onclick="exportQuotations('csv'); return false;"><i class="bi bi-filetype-csv text-muted me-2"></i>Export CSV</a></li>
            <li><a class="dropdown-item py-2" href="#" onclick="exportQuotations('excel'); return false;"><i class="bi bi-file-earmark-excel text-muted me-2"></i>Export Excel</a></li>
          </ul>
        </div>

        <button class="tbtn tbtn-primary" id="btnAddQuotation" onclick="location.href='new_quotation.php'">
          <i class="bi bi-plus-lg"></i> New Quotation
        </button>
        <div id="currentDate" class="live-date"></div>
      </div>
    </header>

  <div class="quotations-shell px-2 pb-4">


    <!-- KPI STAT CARDS -->
    <section class="quotations-kpi-grid">
      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-gold">
          <i class="bi bi-file-earmark-spreadsheet"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Total Quotations</div>
          <div class="leads-kpi-value" id="kpiTotalQuotations">0</div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-blue">
          <i class="bi bi-pencil-square"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Draft Proposals</div>
          <div class="leads-kpi-value" id="kpiDraftQuotations">0</div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-amber">
          <i class="bi bi-envelope"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Out for Review</div>
          <div class="leads-kpi-value" id="kpiSentQuotations">0</div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-green">
          <i class="bi bi-check-circle"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Approved Projects</div>
          <div class="leads-kpi-value" id="kpiApprovedQuotations">0</div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-violet">
          <i class="bi bi-clock-history"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Revised History</div>
          <div class="leads-kpi-value" id="kpiRevisedQuotations">0</div>
        </div>
      </div>
    </section>

    
    <!-- DATA GRID & TABLE -->
    <section class="bg-white border rounded shadow-sm overflow-hidden" style="font-family: 'Inter', 'Poppins', sans-serif;">
      <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
        <h5 class="m-0 fw-bold" style="color: #0f172a; font-size:16px;">
          All Quotations
          <span class="badge rounded-pill ms-2" id="quotationsCountBadge" style="background-color: #f1f5f9; color:#475569; font-weight:600; font-size:12px;">0</span>
        </h5>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-light border fw-semibold shadow-none" id="btnColumns" style="color:#475569;">
            <i class="bi bi-layout-three-columns me-1"></i> Columns
          </button>
          <select id="qPerPage" class="form-select form-select-sm shadow-none w-auto fw-semibold text-muted">
            <option value="10">10 per page</option>
            <option value="25">25 per page</option>
            <option value="50">50 per page</option>
          </select>
        </div>
      </div>

      </div>

      <div class="leads-table-wrap">
        <table class="q-table" id="quotationsTable">
          <thead>
            <tr>
              <th>Quotation No</th>
              <th>Version</th>
              <th>Client Name</th>
              <th>Project Title</th>
              <th class="text-end">Grand Total</th>
              <th>Status</th>
              <th>Created Date</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="quotationsTableBody">
            <!-- Rendered dynamically -->
          </tbody>
        </table>

        <!-- Empty State -->
        <div class="leads-empty-state" id="quotationsEmptyState" style="display:none;">
          <div class="leads-empty-icon">
            <i class="bi bi-file-earmark-spreadsheet"></i>
          </div>
          <h6>No quotations found</h6>
          <p>Create your first quotation draft to get started.</p>
          <button class="tbtn tbtn-primary" id="btnAddQuotationEmpty">
            <i class="bi bi-plus-lg"></i> New Quotation
          </button>
        </div>
      </div>

      <!-- Footer / Pagination -->
      <div class="leads-table-footer">
        <div class="leads-pagination-info" id="qPaginationInfo">
          Showing 0 to 0 of 0 quotations
        </div>
        <div class="leads-pagination-controls">
          <div class="leads-per-page">
            <select id="qPerPage" class="leads-tselect">
              <option value="10">10 per page</option>
              <option value="25">25 per page</option>
              <option value="50">50 per page</option>
            </select>
          </div>
          <div class="leads-page-nav" id="qPageNav">
            <!-- Pagination Controls -->
          </div>
        </div>
      </div>
    </section>

  </div>
</div>

<!-- ==========================================
     COMPOSER / EDIT MODAL
=========================================== -->
<div class="leads-modal" id="quotationModal" role="dialog" aria-modal="true" aria-labelledby="qModalTitle">
  <div class="leads-modal-box leads-modal-box-wide">
    <div class="leads-modal-header">
      <h5 id="qModalTitle"><i class="bi bi-file-earmark-plus"></i> Compose Quotation &amp; BOQ</h5>
      <button class="leads-modal-close" id="btnCloseQModal" aria-label="Close modal">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="leads-modal-body">
      <form id="quotationForm" novalidate>
        <input type="hidden" id="qEditId" name="id" value="">

        <!-- Link CRM Lead Dropdown -->
        <div class="leads-form-row align-items-end">
          <div class="leads-field-group col-md-6">
            <label for="linkLeadSelect" class="leads-label">Link CRM Lead (Optional)</label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-person-lines-fill"></i>
              <select id="linkLeadSelect" name="lead_id" class="leads-input">
                <option value="">Select CRM Lead</option>
              </select>
            </div>
          </div>
          <div class="col-md-6 pb-2 text-muted small">
            Selecting a CRM Lead will automatically pre-populate client details.
          </div>
        </div>

        <hr class="my-3">

        <h6 class="form-section-title">Client Details</h6>
        <div class="leads-form-row">
          <div class="leads-field-group">
            <label for="qClientName" class="leads-label">Client Contact Person <span class="req">*</span></label>
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

        <div class="leads-field-group leads-field-full">
          <label for="qClientAddress" class="leads-label">Billing/Site Address</label>
          <div class="leads-field-icon-wrap">
            <i class="bi bi-geo-alt"></i>
            <textarea id="qClientAddress" name="client_address" class="leads-input leads-textarea" rows="2" placeholder="e.g. 123, Sector 4, Bangalore"></textarea>
          </div>
        </div>

        <h6 class="form-section-title">Proposal Details</h6>
        <div class="leads-form-row">
          <div class="leads-field-group col-md-8">
            <label for="qTitle" class="leads-label">Proposal / Project Title <span class="req">*</span></label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-card-heading"></i>
              <input type="text" id="qTitle" name="title" class="leads-input" placeholder="e.g. Villa Civil Work &amp; Structural Design" required>
            </div>
          </div>
          <div class="leads-field-group col-md-4" id="qStatusContainer" style="display: none;">
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

        <h6 class="form-section-title d-flex justify-content-between align-items-center">
          <span>Bill of Quantities (BOQ)</span>
        </h6>

        <!-- BOQ categories sections -->
        <div id="boqContainer">
          <!-- Dynamically populated category sections -->
        </div>

        <h6 class="form-section-title">Calculations &amp; Terms</h6>
        <div class="boq-calc-footer">
          <div>
            <label for="qNotes" class="leads-label">Notes &amp; Terms &amp; Conditions</label>
            <textarea id="qNotes" name="notes" class="leads-input leads-textarea" rows="4" placeholder="e.g. 1. Rates are inclusive of material transport. 2. Payments to be made in stages..."></textarea>
          </div>
          <div>
            <table class="boq-summary-table">
              <tr>
                <td class="boq-summary-label">Subtotal</td>
                <td class="boq-summary-value" id="calcSubtotal">₹0.00</td>
              </tr>
              <tr>
                <td class="boq-summary-label">GST Rate (%)</td>
                <td>
                  <input type="number" id="qTaxRate" name="tax_rate" class="boq-input text-end" value="18.00" step="0.01" style="width: 100px; float: right;">
                </td>
              </tr>
              <tr>
                <td class="boq-summary-label">GST Tax Amount</td>
                <td class="boq-summary-value" id="calcTaxAmount">₹0.00</td>
              </tr>
              <tr>
                <td class="boq-summary-label">Discount (₹)</td>
                <td>
                  <input type="number" id="qDiscount" name="discount" class="boq-input text-end" value="0.00" step="0.01" style="width: 120px; float: right;">
                </td>
              </tr>
              <tr>
                <td class="boq-summary-label" style="font-size: 15px; color: #B8860B;">Grand Total</td>
                <td class="boq-summary-value" style="font-size: 15px; color: #B8860B;" id="calcGrandTotal">₹0.00</td>
              </tr>
            </table>
          </div>
        </div>

      </form>
    </div>
    <div class="leads-modal-footer">
      <button class="tbtn tbtn-ghost" id="btnCancelQModal">Cancel</button>
      <button class="tbtn tbtn-primary" id="btnSaveQuotation">
        <i class="bi bi-check-lg"></i> Save Quotation
      </button>
    </div>
  </div>
</div>

<!-- ==========================================
     VIEW / DETAILS WORKSPACE MODAL
=========================================== -->
<div class="leads-modal" id="viewQuotationModal" role="dialog" aria-modal="true" aria-labelledby="viewQModalTitle">
  <div class="leads-modal-box leads-modal-box-workspace">
    <div class="leads-modal-header">
      <h5 id="viewQModalTitle"><i class="bi bi-file-earmark-text"></i> Quotation Workspace &amp; BOQ Detail</h5>
      <div class="d-flex align-items-center gap-2">
        <button class="tbtn tbtn-ghost py-1 px-2" id="btnPrintQuotation" title="Print BOQ PDF">
          <i class="bi bi-printer"></i> Print PDF
        </button>
        <button class="leads-modal-close" id="btnCloseViewQModal" aria-label="Close">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    </div>
    <div class="leads-modal-body p-0">
      <div class="workspace-shell">
        <!-- Left details panel -->
        <div class="workspace-left">
          
          <div class="client-profile-header">
            <div>
              <span class="q-badge" id="viewQStatusBadge">Draft</span>
              <h4 class="mt-2 mb-1" id="viewQNumberTitle">QTN-YYYY-XXXX</h4>
              <p class="text-muted m-0" id="viewQTitle">Villa Proposal</p>
            </div>
            <div class="text-end">
              <span class="small text-muted">Grand Total:</span>
              <h3 class="text-gold m-0" id="viewQGrandTotal">₹0.00</h3>
            </div>
          </div>

          <div class="workspace-panel-section mt-3">
            <h6 class="form-section-title">General Information</h6>
            <div class="row">
              <div class="col-md-6 mb-2">
                <span class="small text-muted d-block">Client Contact:</span>
                <strong id="viewQClientName">Rajesh</strong>
              </div>
              <div class="col-md-6 mb-2">
                <span class="small text-muted d-block">Company Name:</span>
                <strong id="viewQClientCompany">-</strong>
              </div>
              <div class="col-md-12 mb-2">
                <span class="small text-muted d-block">Billing/Site Address:</span>
                <strong id="viewQClientAddress">-</strong>
              </div>
            </div>
          </div>

          <div class="workspace-panel-section">
            <h6 class="form-section-title">Bill of Quantities (BOQ) Summary</h6>
            <div class="table-responsive">
              <table class="boq-grid-table w-100" id="viewQItemsTable">
                <thead>
                  <tr>
                    <th>Category</th>
                    <th>Description</th>
                    <th class="text-center">Unit</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
                  </tr>
                </thead>
                <tbody id="viewQItemsTableBody">
                  <!-- Rendered dynamically -->
                </tbody>
              </table>
            </div>
          </div>

          <div class="workspace-panel-section">
            <div class="row">
              <div class="col-md-7">
                <span class="small text-muted d-block">Notes &amp; Conditions:</span>
                <div class="p-2 bg-light rounded text-muted small" id="viewQNotes" style="white-space: pre-wrap;">-</div>
              </div>
              <div class="col-md-5">
                <table class="w-100 small">
                  <tr>
                    <td class="py-1 text-muted">Subtotal:</td>
                    <td class="py-1 text-end" id="viewQSubtotal">₹0.00</td>
                  </tr>
                  <tr>
                    <td class="py-1 text-muted">GST Tax (<span id="viewQTaxRate">18</span>%):</td>
                    <td class="py-1 text-end" id="viewQTaxAmount">₹0.00</td>
                  </tr>
                  <tr>
                    <td class="py-1 text-muted">Discount:</td>
                    <td class="py-1 text-end text-danger" id="viewQDiscount">-₹0.00</td>
                  </tr>
                  <tr class="border-top border-dark font-weight-bold">
                    <td class="py-2 text-dark font-weight-bold">Grand Total:</td>
                    <td class="py-2 text-end text-gold font-weight-bold" id="viewQGrandTotalFooter">₹0.00</td>
                  </tr>
                </table>
              </div>
            </div>
          </div>

        </div>

        <!-- Right version history / revisions panel -->
        <div class="workspace-right">
          <div class="workspace-panel-section h-100">
            <h6 class="form-section-title"><i class="bi bi-clock-history"></i> Revision Trail</h6>
            
            <div class="q-revisions-panel">
              <div class="q-revisions-title">Available Versions</div>
              <div class="q-revisions-list" id="viewQRevisionsList">
                <!-- Rendered dynamically -->
              </div>
            </div>

            <div class="mt-4 d-flex flex-column gap-2" id="workspaceActionButtons">
              <!-- Dynamically rendered action buttons like Edit, Revise, Convert to Project -->
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="leads-modal-footer">
      <button class="tbtn tbtn-ghost" id="btnCloseViewQModalFooter">Close Workspace</button>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="js/sidebar.js?v=6"></script>
<script src="js/quotations.js?v=6"></script>
<script src="js/script.js?v=6"></script>
</body>
</html>
