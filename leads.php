<?php
// TEMPORARY DEVELOPMENT MODE - AUTH DISABLED
// require_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Leads Management – JGC Constructions ERP. Manage and track all your construction leads in one place.">
  <title>Leads – JGC Constructions</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Project CSS -->
  <link rel="stylesheet" href="css/sidebar.css?v=6">
  <link rel="stylesheet" href="css/jgc-theme.css?v=6">
  <link rel="stylesheet" href="css/leads.css?v=6">
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

    <!-- ── TOPBAR ───────────────────────── -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h1 class="page-title">Leads</h1>
          <p class="page-sub">Manage and track all your leads in one place.</p>
        </div>
      </div>

      <div class="topbar-right">
        <button class="tbtn tbtn-ghost" id="btnImportLeads">
          <i class="bi bi-download"></i> Import Leads
        </button>
        <div class="add-lead-split">
          <button class="tbtn tbtn-primary" id="btnAddLead">
            <i class="bi bi-plus-lg"></i> Add Lead
          </button>
          <button class="tbtn tbtn-primary tbtn-split-arrow" id="btnAddLeadDropdown" aria-label="More options">
            <i class="bi bi-chevron-down"></i>
          </button>
        </div>
        <div id="currentDate" class="live-date"></div>
      </div>
    </header>

  <div class="leads-shell">

    <!-- ── KPI STAT CARDS ──────────────── -->
    <section class="leads-kpi-grid">

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-gold">
          <i class="bi bi-people"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Total Leads</div>
          <div class="leads-kpi-value" id="kpiTotalLeads"><!-- DB: total leads --></div>
          <div class="leads-kpi-trend trend-up">
            <i class="bi bi-arrow-up-right"></i>
            <span id="kpiTotalTrend">--</span> vs last month
          </div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-blue">
          <i class="bi bi-person-plus"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">New Leads</div>
          <div class="leads-kpi-value" id="kpiNewLeads"><!-- DB: new leads --></div>
          <div class="leads-kpi-trend trend-up">
            <i class="bi bi-arrow-up-right"></i>
            <span id="kpiNewTrend">--</span> vs last month
          </div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-amber">
          <i class="bi bi-clock-history"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Follow-up Pending</div>
          <div class="leads-kpi-value" id="kpiFollowup"><!-- DB --></div>
          <div class="leads-kpi-trend trend-down">
            <i class="bi bi-arrow-down-right"></i>
            <span id="kpiFollowupTrend">--</span> vs last month
          </div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-violet">
          <i class="bi bi-geo-alt"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Site Visits</div>
          <div class="leads-kpi-value" id="kpiSiteVisits"><!-- DB --></div>
          <div class="leads-kpi-trend trend-up">
            <i class="bi bi-arrow-up-right"></i>
            <span id="kpiSiteVisitsTrend">--</span> vs last month
          </div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-teal">
          <i class="bi bi-file-earmark-text"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Quotations Sent</div>
          <div class="leads-kpi-value" id="kpiQuotations"><!-- DB --></div>
          <div class="leads-kpi-trend trend-up">
            <i class="bi bi-arrow-up-right"></i>
            <span id="kpiQuotationsTrend">--</span> vs last month
          </div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-green">
          <i class="bi bi-trophy"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Won Leads</div>
          <div class="leads-kpi-value" id="kpiWon"><!-- DB --></div>
          <div class="leads-kpi-trend trend-up">
            <i class="bi bi-arrow-up-right"></i>
            <span id="kpiWonTrend">--</span> vs last month
          </div>
        </div>
      </div>

      <div class="leads-kpi-card">
        <div class="leads-kpi-icon lki-red">
          <i class="bi bi-x-circle"></i>
        </div>
        <div class="leads-kpi-body">
          <div class="leads-kpi-label">Lost Leads</div>
          <div class="leads-kpi-value" id="kpiLost"><!-- DB --></div>
          <div class="leads-kpi-trend trend-down">
            <i class="bi bi-arrow-down-right"></i>
            <span id="kpiLostTrend">--</span> vs last month
          </div>
        </div>
      </div>

    </section>

    
    <!-- ── CHARTS ROW 1 ─────── -->
    <section class="leads-analytics-row row-3">
<!-- Leads Funnel -->
      <div class="leads-panel-card funnel-card">
        <div class="leads-panel-head">
          <h3><i class="bi bi-funnel"></i> Leads Funnel</h3>
          <div class="leads-select-wrap">
            <select id="funnelPeriod" class="leads-tselect">
              <option value="this_month">This Month</option>
              <option value="last_month">Last Month</option>
              <option value="this_quarter">This Quarter</option>
            </select>
          </div>
        </div>
        <div class="funnel-body">
          <div class="funnel-bars" id="funnelBars">
            <!-- JS-rendered funnel bars -->
          </div>
          <div class="funnel-legend" id="funnelLegend">
            <!-- JS-rendered legend -->
          </div>
        </div>
        <div class="funnel-metrics-row">
          <div class="funnel-metric">
            <div class="funnel-metric-label">Conversion Rate</div>
            <div class="funnel-metric-value text-gold" id="funnelConvRate"><!-- DB --></div>
          </div>
          <div class="funnel-metric">
            <div class="funnel-metric-label">Avg. Conversion Time</div>
            <div class="funnel-metric-value text-gold" id="funnelAvgTime"><!-- DB --></div>
          </div>
        </div>
      </div>

      
<!-- Leads by Source (Donut) -->
      <div class="leads-panel-card source-card">
        <div class="leads-panel-head">
          <h3><i class="bi bi-pie-chart"></i> Leads by Source</h3>
          <div class="leads-select-wrap">
            <select id="sourcePeriod" class="leads-tselect">
              <option value="this_month">This Month</option>
              <option value="last_month">Last Month</option>
            </select>
          </div>
        </div>
        <div class="donut-wrap">
          <canvas id="sourceDonutChart" width="180" height="180" aria-label="Leads by source donut chart"></canvas>
          <div class="donut-center-label">
            <div class="donut-total-num" id="donutTotalNum"><!-- DB --></div>
            <div class="donut-total-sub">Total</div>
          </div>
        </div>
        <div class="donut-legend" id="donutLegend">
          <!-- JS-rendered legend items -->
        </div>
      </div>

      
<!-- Leads by Status -->
      <div class="leads-panel-card status-chart-card">
        <div class="leads-panel-head">
          <h3><i class="bi bi-bar-chart-steps"></i> Leads by Status</h3>
          <div class="leads-select-wrap">
            <select id="statusPeriod" class="leads-tselect">
              <option value="this_month">This Month</option>
              <option value="last_month">Last Month</option>
            </select>
          </div>
        </div>
        <div class="status-chart-list" id="statusChartList">
          <!-- JS-rendered status rows -->
        </div>
        <a href="#allLeadsTable" class="view-full-report">
          View Full Report <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      
    </section>
    
    <!-- ── CHARTS ROW 2 ─────── -->
    <section class="leads-analytics-row row-2 mb-3 mt-3">
<!-- Upcoming Follow-ups -->
      <div class="leads-panel-card followup-card">
        <div class="leads-panel-head">
          <h3><i class="bi bi-calendar2-check"></i> Upcoming Follow-ups</h3>
          <a href="#" class="view-all-link">View All</a>
        </div>
        <div class="followup-list" id="followupList">
          <!-- JS-rendered follow-up cards -->
        </div>
      </div>

    
<div class="leads-panel-card owner-performance-card">
      <div class="leads-panel-head">
        <h3><i class="bi bi-person-badge"></i> Lead Owner Performance</h3>
        <div class="leads-select-wrap">
          <select id="ownerPerfPeriod" class="leads-tselect">
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="this_quarter">This Quarter</option>
          </select>
        </div>
      </div>
      <div class="owner-perf-list" id="ownerPerfList">
        <!-- JS-rendered owner performance rows -->
      </div>
      <a href="#" class="view-full-report">
        View Full Report <i class="bi bi-arrow-right"></i>
      </a>
    </div>
    </section>
    

    <!-- ── ALL LEADS TABLE ─────────────── -->
    <section class="leads-panel-card table-section" id="allLeadsTable">

      <!-- Table Toolbar -->
      <div class="leads-table-toolbar">
        <div class="table-toolbar-left">
          <h3 class="table-toolbar-title">
            All Leads
            <span class="leads-count-badge" id="leadsCountBadge"><!-- DB --></span>
          </h3>
        </div>
        <div class="table-toolbar-right">
          <div class="leads-search-box">
            <i class="bi bi-search"></i>
            <input
              type="text"
              id="leadsSearchInput"
              placeholder="Search leads..."
              autocomplete="off"
            >
          </div>
          <div class="leads-filter-chip">
            <i class="bi bi-circle-half"></i>
            <select id="filterStatus" class="leads-tselect">
              <option value="">Status</option>
              <option value="New Lead">New Lead</option>
              <option value="Contacted">Contacted</option>
              <option value="Meeting Scheduled">Meeting Scheduled</option>
              <option value="Site Visit Scheduled">Site Visit Scheduled</option>
              <option value="Quotation Sent">Quotation Sent</option>
              <option value="Negotiation">Negotiation</option>
              <option value="Won">Won</option>
              <option value="Lost">Lost</option>
              <option value="Hold">Hold</option>
              <option value="Not Interested">Not Interested</option>
              <option value="Duplicate">Duplicate</option>
            </select>
          </div>
          <div class="leads-filter-chip">
            <i class="bi bi-diagram-3"></i>
            <select id="filterSource" class="leads-tselect">
              <option value="">Source</option>
              <option value="Website">Website</option>
              <option value="Referral">Referral</option>
              <option value="Social Media">Social Media</option>
              <option value="Walk-in">Walk-in</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="leads-filter-chip">
            <i class="bi bi-person"></i>
            <select id="filterOwner" class="leads-tselect">
              <option value="">Owner</option>
              <!-- DB: populate owners -->
            </select>
          </div>
          <button class="tbtn tbtn-ghost" id="btnMoreFilters">
            <i class="bi bi-sliders"></i> More Filters
          </button>
          <button class="tbtn tbtn-ghost" id="btnColumns">
            <i class="bi bi-layout-three-columns"></i> Columns
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="leads-table-wrap">
        <table class="leads-table" id="leadsTable">
          <thead>
            <tr>
              <th class="col-lead-name">Lead Name</th>
              <th class="col-phone">Phone</th>
              <th class="col-requirement">Requirement</th>
              <th class="col-budget">Budget</th>
              <th class="col-source">Source</th>
              <th class="col-owner">Assigned To</th>
              <th class="col-status">Status</th>
              <th class="col-created">Created Date</th>
              <th class="col-actions">Actions</th>
            </tr>
          </thead>
          <tbody id="leadsTableBody">
            <!-- JS / DB rendered rows -->
          </tbody>
        </table>

        <!-- Empty State -->
        <div class="leads-empty-state" id="leadsEmptyState" style="display:none;">
          <div class="leads-empty-icon">
            <i class="bi bi-person-lines-fill"></i>
          </div>
          <h6>No leads found</h6>
          <p>Add your first lead or adjust your filters to see results.</p>
          <button class="tbtn tbtn-primary" id="btnAddLeadEmpty">
            <i class="bi bi-plus-lg"></i> Add Lead
          </button>
        </div>
      </div>

      <!-- Table Footer / Pagination -->
      <div class="leads-table-footer">
        <div class="leads-pagination-info" id="leadsPaginationInfo">
          <!-- JS: Showing 1 to X of Y leads -->
        </div>
        <div class="leads-pagination-controls">
          <div class="leads-per-page">
            <select id="leadsPerPage" class="leads-tselect">
              <option value="10">10 per page</option>
              <option value="25">25 per page</option>
              <option value="50">50 per page</option>
            </select>
          </div>
          <div class="leads-page-nav" id="leadsPageNav">
            <!-- JS-rendered pagination -->
          </div>
        </div>
      </div>

    </section>

    

  </div><!-- /.leads-shell -->
</div><!-- /.main -->

<!-- ══════════════════════════════════════
     ADD LEAD MODAL
══════════════════════════════════════ -->
<div class="leads-modal" id="addLeadModal" role="dialog" aria-modal="true" aria-labelledby="addLeadModalTitle">
  <div class="leads-modal-box">
    <div class="leads-modal-header">
      <h5 id="addLeadModalTitle"><i class="bi bi-person-plus"></i> Add New Lead</h5>
      <button class="leads-modal-close" id="btnCloseLeadModal" aria-label="Close modal">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="leads-modal-body">
      <form id="addLeadForm" novalidate>

        <!-- Hidden: populated by editLead() -->
        <input type="hidden" id="leadEditId" name="id" value="">

        <!-- Section: Client Information -->
        <h6 class="form-section-title">Client Information</h6>
        <div class="leads-form-row">
          <div class="leads-field-group">
            <label for="leadName" class="leads-label">Lead Name <span class="req">*</span></label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-person"></i>
              <input type="text" id="leadName" name="lead_name" class="leads-input" placeholder="e.g. Rajesh Kumar" required>
            </div>
          </div>
          <div class="leads-field-group">
            <label for="leadCompany" class="leads-label">Company Name</label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-building"></i>
              <input type="text" id="leadCompany" name="company" class="leads-input" placeholder="e.g. ABC Builders">
            </div>
          </div>
        </div>

        <div class="leads-form-row">
          <div class="leads-field-group">
            <label for="leadPhone" class="leads-label">Phone <span class="req">*</span></label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-telephone"></i>
              <input type="tel" id="leadPhone" name="phone" class="leads-input" placeholder="e.g. +91 98765 43210" required>
            </div>
          </div>
          <div class="leads-field-group">
            <label for="leadEmail" class="leads-label">Email Address</label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-envelope"></i>
              <input type="email" id="leadEmail" name="email" class="leads-input" placeholder="e.g. email@example.com">
            </div>
          </div>
        </div>

        <div class="leads-form-row">
          <div class="leads-field-group">
            <label for="leadGstNumber" class="leads-label">GST Number</label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-receipt"></i>
              <input type="text" id="leadGstNumber" name="gst_number" class="leads-input" placeholder="e.g. 22AAAAA0000A1Z5">
            </div>
          </div>
          <div class="leads-field-group">
            <!-- empty/flex filler -->
          </div>
        </div>

        <div class="leads-field-group leads-field-full">
          <label for="leadAddress" class="leads-label">Billing/Site Address</label>
          <div class="leads-field-icon-wrap">
            <i class="bi bi-geo-alt"></i>
            <textarea id="leadAddress" name="address" class="leads-input leads-textarea" rows="2" placeholder="e.g. Plot 42, Sector 5, Bangalore"></textarea>
          </div>
        </div>

        <!-- Section: Requirement Details -->
        <h6 class="form-section-title">Requirement Details</h6>
        <div class="leads-field-group leads-field-full">
          <label for="leadProjectType" class="leads-label">Project Type / Requirement</label>
          <div class="leads-field-icon-wrap">
            <i class="bi bi-building-add"></i>
            <input type="text" id="leadProjectType" name="project_type" class="leads-input" placeholder="e.g. Residential Villa, Commercial Plaza, Renovation">
          </div>
        </div>

        <!-- Section: Budget & Timeline -->
        <h6 class="form-section-title">Budget & Timeline</h6>
        <div class="leads-form-row">
          <div class="leads-field-group">
            <label for="leadBudget" class="leads-label">Estimated Budget (INR)</label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-cash-coin"></i>
              <input type="number" id="leadBudget" name="budget" class="leads-input" placeholder="e.g. 5000000">
            </div>
          </div>
          <div class="leads-field-group">
            <label for="leadFollowupDate" class="leads-label">Next Follow-up Date</label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-calendar3"></i>
              <input type="date" id="leadFollowupDate" name="followup_date" class="leads-input">
            </div>
          </div>
        </div>

        <!-- Section: Lead Source, Status & Assignment -->
        <h6 class="form-section-title">Lead Assignment & Status</h6>
        <div class="leads-form-row">
          <div class="leads-field-group">
            <label for="leadSource" class="leads-label">Lead Source</label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-diagram-3"></i>
              <select id="leadSource" name="source" class="leads-input">
                <option value="">Select Source</option>
                <option value="Website">Website</option>
                <option value="Referral">Referral</option>
                <option value="Social Media">Social Media</option>
                <option value="Walk-in">Walk-in</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          <div class="leads-field-group">
            <label for="leadStatus" class="leads-label">Lead Status</label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-circle-half"></i>
              <select id="leadStatus" name="status" class="leads-input">
                <option value="New Lead">New Lead</option>
                <option value="Contacted">Contacted</option>
                <option value="Meeting Scheduled">Meeting Scheduled</option>
                <option value="Site Visit Scheduled">Site Visit Scheduled</option>
                <option value="Quotation Sent">Quotation Sent</option>
                <option value="Negotiation">Negotiation</option>
                <option value="Won">Won</option>
                <option value="Lost">Lost</option>
                <option value="Hold">Hold</option>
                <option value="Not Interested">Not Interested</option>
                <option value="Duplicate">Duplicate</option>
              </select>
            </div>
          </div>
        </div>

        <div class="leads-form-row">
          <div class="leads-field-group">
            <label for="leadOwner" class="leads-label">Assigned To (Owner)</label>
            <div class="leads-field-icon-wrap">
              <i class="bi bi-person-badge"></i>
              <select id="leadOwner" name="owner" class="leads-input">
                <option value="">Assign Owner</option>
                <!-- DB: populate owners -->
              </select>
            </div>
          </div>
          <div class="leads-field-group">
            <!-- empty/flex filler -->
          </div>
        </div>

        <!-- Section: Notes -->
        <h6 class="form-section-title">Notes</h6>
        <div class="leads-field-group leads-field-full">
          <label for="leadNotes" class="leads-label">Internal Notes / Requirements details</label>
          <textarea id="leadNotes" name="notes" class="leads-input leads-textarea" rows="3" placeholder="Add any relevant notes about this lead..."></textarea>
        </div>

      </form>
    </div>
    <div class="leads-modal-footer">
      <button class="tbtn tbtn-ghost" id="btnCancelLead">Cancel</button>
      <button class="tbtn tbtn-primary" id="btnSaveLead">
        <i class="bi bi-check-lg"></i> Save Lead
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     LEAD DETAIL / VIEW MODAL
══════════════════════════════════════ -->
<div class="leads-modal" id="viewLeadModal" role="dialog" aria-modal="true" aria-labelledby="viewLeadModalTitle">
  <div class="leads-modal-box leads-modal-box-wide">
    <div class="leads-modal-header">
      <h5 id="viewLeadModalTitle"><i class="bi bi-eye"></i> Lead Details</h5>
      <button class="leads-modal-close" id="btnCloseViewModal" aria-label="Close">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="leads-modal-body" id="viewLeadContent">
      <!-- JS-rendered lead details -->
    </div>
    <div class="leads-modal-footer">
      <button class="tbtn tbtn-ghost" id="btnCloseViewModalFooter">Close</button>
      <button class="tbtn tbtn-primary" id="btnEditFromView">
        <i class="bi bi-pencil"></i> Edit Lead
      </button>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="js/sidebar.js"></script>
<script src="js/leads.js"></script>
<script src="js/script.js?v=3"></script>
</body>
</html>
