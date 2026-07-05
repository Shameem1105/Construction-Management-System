import re

with open('quotations.php', 'r', encoding='utf-8') as f:
    html = f.read()

# Replace Header
header_html = """
    <!-- TOPBAR (Redesigned ERP Style) -->
    <header class="d-flex align-items-center justify-content-between bg-white px-4 py-3 border-bottom shadow-sm rounded-top mb-3" style="font-family: 'Inter', 'Poppins', sans-serif;">
      <div class="d-flex align-items-center gap-3">
        <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar" style="background:none; border:none; font-size:24px; color:#475569;">
          <i class="bi bi-list"></i>
        </button>
        <div>
          <h4 class="m-0 fw-bold" style="color: #0f172a;">Quotation</h4>
        </div>
      </div>
      
      <div class="d-flex align-items-center gap-3 flex-grow-1 mx-4" style="max-width: 450px;">
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" id="qSearchInput" class="form-control bg-light border-start-0 shadow-none" placeholder="Search by Quotation No, Client, Project..." style="font-size:14px;">
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
          <button class="btn btn-light border dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown" style="font-size:14px; color:#475569;">
            <i class="bi bi-download text-muted me-1"></i> Export
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="font-size:14px;">
            <li><a class="dropdown-item py-2" href="#" onclick="exportQuotations('csv'); return false;"><i class="bi bi-filetype-csv text-muted me-2"></i>Export CSV</a></li>
            <li><a class="dropdown-item py-2" href="#" onclick="exportQuotations('excel'); return false;"><i class="bi bi-file-earmark-excel text-muted me-2"></i>Export Excel</a></li>
          </ul>
        </div>
        <button class="btn fw-semibold" id="btnAddQuotation" onclick="location.href='new_quotation.php'" style="background-color: #D4AF37; color:#fff; border:none; font-size:14px; padding: 7px 16px;">
          <i class="bi bi-plus-lg me-1"></i> New Quotation
        </button>
      </div>
    </header>
"""
html = re.sub(r'<!-- TOPBAR -->.*?</header>', header_html, html, flags=re.DOTALL)

# Replace KPI section to fix spacing
kpi_html = """
    <!-- KPI STAT CARDS (6 cards) -->
    <section class="quotations-kpi-grid mb-3 px-2" id="kpiGrid">
"""
html = re.sub(r'<!-- KPI STAT CARDS \(6 cards\) -->\s*<section class="quotations-kpi-grid" id="kpiGrid">', kpi_html, html)

# Replace Filter Section
filter_html = """
    <!-- FILTER PANEL (Horizontal Row) -->
    <div class="bg-white px-4 py-3 border shadow-sm rounded mb-3" id="qFilterPanel" style="font-family: 'Inter', 'Poppins', sans-serif;">
      <div class="row g-3 align-items-end">
        <div class="col">
          <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px; text-transform:uppercase; letter-spacing:0.5px;">Client</label>
          <input type="text" id="filterClient" class="form-control form-control-sm shadow-none" placeholder="Search client...">
        </div>
        <div class="col">
          <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px; text-transform:uppercase; letter-spacing:0.5px;">Project</label>
          <input type="text" id="filterProject" class="form-control form-control-sm shadow-none" placeholder="Search project...">
        </div>
        <div class="col">
          <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px; text-transform:uppercase; letter-spacing:0.5px;">Sales Person</label>
          <input type="text" id="filterSales" class="form-control form-control-sm shadow-none" placeholder="Search sales...">
        </div>
        <div class="col">
          <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px; text-transform:uppercase; letter-spacing:0.5px;">Status</label>
          <select id="filterStatus" class="form-select form-select-sm shadow-none">
            <option value="">All Statuses</option>
            <option value="Draft">Draft</option>
            <option value="Sent">Sent</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Revised">Revised</option>
          </select>
        </div>
        <div class="col">
          <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px; text-transform:uppercase; letter-spacing:0.5px;">Date From</label>
          <input type="date" id="filterDateFrom" class="form-control form-control-sm shadow-none">
        </div>
        <div class="col">
          <label class="form-label text-muted fw-semibold mb-1" style="font-size: 12px; text-transform:uppercase; letter-spacing:0.5px;">Date To</label>
          <input type="date" id="filterDateTo" class="form-control form-control-sm shadow-none">
        </div>
        <div class="col-auto d-flex gap-2">
          <button class="btn btn-light border btn-sm fw-semibold shadow-none px-3 py-1" id="btnResetFilters" style="color:#475569;">Reset</button>
          <button class="btn btn-sm fw-semibold shadow-none px-3 py-1" id="btnApplyFilters" style="background-color: #D4AF37; color:#fff; border:none;">Apply Filters</button>
        </div>
      </div>
    </div>
"""
html = re.sub(r'<!-- FILTER PANEL -->.*?</div>\s*</div>\s*</div>', filter_html, html, flags=re.DOTALL)

# Modify Table Toolbar
table_toolbar = """
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
"""
html = re.sub(r'<!-- DATA GRID & TABLE -->\s*<section class="q-table-section">.*?</div>\s*</div>', table_toolbar, html, flags=re.DOTALL)

# Ensure shell has no margin top if it doesn't need it and use proper padding
html = re.sub(r'<div class="quotations-shell">', r'<div class="quotations-shell px-2 pb-4">', html)

with open('quotations.php', 'w', encoding='utf-8') as f:
    f.write(html)

print("Updated quotations.php layout")
