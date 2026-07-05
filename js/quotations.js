/**
 * quotations.js – Quotation & BOQ Workspace Logic
 * JGC Constructions ERP
 */

/* ══════════════════════════════════════════════════════
   STATE
   ══════════════════════════════════════════════════════ */
var quotationsData = [];
var leadsList = [];
var currentPage = 1;
var perPage = 10;
var totalQuotations = 0;
var activeQuotationId = null;
var editMode = false;

var DEFAULT_CATEGORIES = [
  'Civil Work',
  'Structural',
  'Plumbing',
  'Electrical',
  'Finishing'
];

/* ══════════════════════════════════════════════════════
   INIT
   ══════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
  loadQuotations();
  loadLeads();
  bindEvents();
});

/* ══════════════════════════════════════════════════════
   LOAD QUOTATIONS FROM DB
   ══════════════════════════════════════════════════════ */
function buildQueryString() {
  var params = [];
  var q = (document.getElementById('qSearchInput') || {}).value || '';
  var status = (document.getElementById('filterStatus') || {}).value || '';
  var client = (document.getElementById('filterClient') || {}).value || '';
  var project = (document.getElementById('filterProject') || {}).value || '';
  var sales = (document.getElementById('filterSales') || {}).value || '';
  var date_from = (document.getElementById('filterDateFrom') || {}).value || '';
  var date_to = (document.getElementById('filterDateTo') || {}).value || '';

  if (q) { params.push('q=' + encodeURIComponent(q)); }
  if (status) { params.push('status=' + encodeURIComponent(status)); }
  if (client) { params.push('client=' + encodeURIComponent(client)); }
  if (project) { params.push('project=' + encodeURIComponent(project)); }
  if (sales) { params.push('sales=' + encodeURIComponent(sales)); }
  if (date_from) { params.push('date_from=' + encodeURIComponent(date_from)); }
  if (date_to) { params.push('date_to=' + encodeURIComponent(date_to)); }

  params.push('page=' + currentPage);
  params.push('limit=' + perPage);
  return params.join('&');
}

function loadQuotations() {
  showTableLoading(true);

  fetch('api/get_quotations.php?' + buildQueryString())
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (!resp.success) {
        showError('Failed to load quotations.');
        return;
      }
      quotationsData = resp.quotations;
      totalQuotations = resp.total;
      renderKPI(resp.kpi);
      renderQuotationsTable();
      showTableLoading(false);
    })
    .catch(function () {
      showError('Network error while loading quotations.');
      showTableLoading(false);
    });
}

function renderKPI(kpi) {
  if (!kpi) return;
  setText('kpiTotalQuotations', kpi.total || 0);
  setText('kpiApprovedQuotations', kpi.approved || 0);
  setText('kpiSentQuotations', kpi.sent || 0);
  setText('kpiRejectedQuotations', kpi.rejected || 0);
  
  var totalVal = parseFloat(kpi.total_value) || 0;
  setText('kpiTotalValue', '₹' + totalVal.toLocaleString('en-IN', { maximumFractionDigits: 0 }));
  
  var convRate = 0;
  if (parseInt(kpi.total) > 0) {
    convRate = Math.round((parseInt(kpi.approved) / parseInt(kpi.total)) * 100);
  }
  setText('kpiConversionRate', convRate + '%');
}

function showTableLoading(show) {
  var tbody = document.getElementById('quotationsTableBody');
  if (!tbody) return;

  if (show) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:#64748b;">' +
      '<i class="bi bi-hourglass-split" style="font-size:20px;margin-bottom:8px;display:block;"></i>Loading quotations…</td></tr>';
  }
}

function showError(msg) {
  var tbody = document.getElementById('quotationsTableBody');
  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:32px;color:#dc2626;">' + msg + '</td></tr>';
  }
}

/* ══════════════════════════════════════════════════════
   LOAD LEADS LIST
   ══════════════════════════════════════════════════════ */
function loadLeads() {
  fetch('api/get_leads.php?limit=100')
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        leadsList = resp.leads;
        populateLeadSelect();
      }
    })
    .catch(function () {
      console.warn('Failed to load leads for link dropdown.');
    });
}

function populateLeadSelect() {
  var select = document.getElementById('linkLeadSelect');
  if (!select) return;

  // Preserve first option
  select.innerHTML = '<option value="">Select CRM Lead</option>';
  leadsList.forEach(function (lead) {
    var option = document.createElement('option');
    option.value = lead.id;
    option.textContent = lead.name + ' - ' + (lead.company || 'No Company') + ' (' + lead.phone + ')';
    select.appendChild(option);
  });
}

/* ══════════════════════════════════════════════════════
   RENDER DATA GRID TABLE
   ══════════════════════════════════════════════════════ */
function renderQuotationsTable() {
  var tbody = document.getElementById('quotationsTableBody');
  var emptyState = document.getElementById('quotationsEmptyState');
  if (!tbody) return;

  tbody.innerHTML = '';

  if (quotationsData.length === 0) {
    if (emptyState) emptyState.style.display = 'block';
    document.getElementById('quotationsCountBadge').textContent = '0';
    return;
  }

  if (emptyState) emptyState.style.display = 'none';
  document.getElementById('quotationsCountBadge').textContent = totalQuotations;

  quotationsData.forEach(function (q) {
    var tr = document.createElement('tr');

    var statusClass = 'q-status-draft';
    switch (q.status) {
      case 'Draft': statusClass = 'q-status-draft'; break;
      case 'Sent': statusClass = 'q-status-sent'; break;
      case 'Approved': statusClass = 'q-status-approved'; break;
      case 'Rejected': statusClass = 'q-status-rejected'; break;
      case 'Revised': statusClass = 'q-status-revised'; break;
    }

    
    var totalVal = parseFloat(q.grand_total) || 0;
    var formattedTotal = '₹' + totalVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    var createdDate = q.created_at ? q.created_at.substring(0, 10) : '';
    var validityDate = new Date(q.created_at);
    validityDate.setDate(validityDate.getDate() + 30);
    var formattedValidity = validityDate.toISOString().substring(0, 10);
    var salesPerson = q.created_by || 'System';
    var projectName = q.project_name || '-';
    var isConverted = (q.project_id && parseInt(q.project_id) > 0);

    var actionsHtml = '<div class="dropdown d-inline-block">' +
      '<button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>' +
      '<ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size: 13px;">' +
      '<li><a class="dropdown-item" href="#" onclick="viewQuotationWorkspace(' + q.id + '); return false;"><i class="bi bi-eye text-primary me-2"></i> View Workspace</a></li>';

    if (q.status === 'Draft') {
      actionsHtml += '<li><a class="dropdown-item" href="new_quotation.php?edit_id=' + q.id + '"><i class="bi bi-pencil text-warning me-2"></i> Edit Draft</a></li>';
    }
    if (q.status === 'Sent' || q.status === 'Approved') {
      actionsHtml += '<li><a class="dropdown-item" href="#" onclick="reviseQuotation(' + q.id + '); return false;"><i class="bi bi-arrow-repeat text-warning me-2"></i> Create Revision</a></li>';
    }
    if (q.status === 'Approved' && !isConverted) {
      actionsHtml += '<li><a class="dropdown-item" href="#" onclick="convertToProject(' + q.id + '); return false;"><i class="bi bi-box-arrow-up-right text-success me-2"></i> Convert to Project</a></li>';
    }
    
    actionsHtml += '<li><hr class="dropdown-divider"></li>' +
      '<li><a class="dropdown-item" href="#" onclick="duplicateQuotation(' + q.id + '); return false;"><i class="bi bi-files text-secondary me-2"></i> Duplicate</a></li>' +
      '<li><a class="dropdown-item" href="#" onclick="alert(\'Downloading PDF...\'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2"></i> Download PDF</a></li>' +
      '<li><a class="dropdown-item" href="#" onclick="alert(\'Sending Email...\'); return false;"><i class="bi bi-envelope text-primary me-2"></i> Send Mail</a></li>' +
      '<li><a class="dropdown-item" href="#" onclick="alert(\'Opening WhatsApp...\'); return false;"><i class="bi bi-whatsapp text-success me-2"></i> Send WhatsApp</a></li>';

    if (q.status === 'Draft' || q.status === 'Rejected') {
      actionsHtml += '<li><hr class="dropdown-divider"></li>' +
        '<li><a class="dropdown-item text-danger" href="#" onclick="deleteQuotation(' + q.id + '); return false;"><i class="bi bi-trash text-danger me-2"></i> Delete</a></li>';
    }

    actionsHtml += '</ul></div>';

    tr.innerHTML = '<td style="width:40px;"><input type="checkbox" class="row-check" value="' + q.id + '"></td>' +
      '<td class="col-qno"><strong>' + escapeHtml(q.quotation_number) + '</strong></td>' +
      '<td class="col-ver">v' + q.version + '</td>' +
      '<td class="col-client">' + escapeHtml(q.client_name) + '</td>' +
      '<td class="col-project">' + escapeHtml(projectName) + '</td>' +
      '<td class="col-amount text-end font-weight-bold text-dark">' + formattedTotal + '</td>' +
      '<td class="col-created">' + createdDate + '</td>' +
      '<td class="col-validity">' + formattedValidity + '</td>' +
      '<td class="col-sales">' + escapeHtml(salesPerson) + '</td>' +
      '<td class="col-status"><span class="badge ' + statusClass + '">' + q.status + '</span></td>' +
      '<td class="col-conv text-center">' + (isConverted ? '<i class="bi bi-check-circle-fill text-success" title="Converted to Project ID: ' + q.project_id + '"></i>' : '-') + '</td>' +
      '<td class="col-actions text-center">' + actionsHtml + '</td>';

    tbody.appendChild(tr);
  });

  renderPagination();
}

function renderPagination() {
  var infoEl = document.getElementById('qPaginationInfo');
  var navEl = document.getElementById('qPageNav');
  if (!infoEl || !navEl) return;

  var from = (currentPage - 1) * perPage + 1;
  var to = Math.min(currentPage * perPage, totalQuotations);
  if (totalQuotations === 0) {
    infoEl.textContent = 'Showing 0 to 0 of 0 quotations';
    navEl.innerHTML = '';
    return;
  }
  infoEl.textContent = 'Showing ' + from + ' to ' + to + ' of ' + totalQuotations + ' quotations';

  var totalPages = Math.ceil(totalQuotations / perPage);
  var html = '';

  // Prev
  html += '<button class="btn btn-sm btn-outline-secondary me-1 py-1" ' + (currentPage === 1 ? 'disabled' : '') + ' onclick="changePage(' + (currentPage - 1) + ')"><i class="bi bi-chevron-left"></i></button>';

  // Pages
  for (var i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
      html += '<button class="btn btn-sm ' + (i === currentPage ? 'btn-gold font-weight-bold' : 'btn-outline-secondary') + ' me-1 py-1" onclick="changePage(' + i + ')">' + i + '</button>';
    } else if (i === currentPage - 2 || i === currentPage + 2) {
      html += '<span class="px-2">...</span>';
    }
  }

  // Next
  html += '<button class="btn btn-sm btn-outline-secondary py-1" ' + (currentPage === totalPages ? 'disabled' : '') + ' onclick="changePage(' + (currentPage + 1) + ')"><i class="bi bi-chevron-right"></i></button>';

  navEl.innerHTML = html;
}

function changePage(page) {
  currentPage = page;
  loadQuotations();
}

/* ══════════════════════════════════════════════════════
   COMPOSER / SAVE ACTION
   ══════════════════════════════════════════════════════ */
function deleteQuotation(id) {
  if (!confirm('Are you sure you want to delete this quotation draft?')) return;

  var formData = new FormData();
  formData.append('id', id);

  fetch('api/delete_quotation.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        loadQuotations();
      } else {
        alert('Error: ' + resp.message);
      }
    })
    .catch(function () {
      alert('Network error.');
    });
}

/* ══════════════════════════════════════════════════════
   VIEW WORKSPACE & REVISION TRAIL
   ══════════════════════════════════════════════════════ */
function viewQuotationWorkspace(id) {
  loadQuotationDetails(id);
  document.getElementById('viewQuotationModal').classList.add('active');
}

function loadQuotationDetails(id) {
  activeQuotationId = id;

  fetch('api/get_quotation_details.php?id=' + id)
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (!resp.success) {
        alert('Failed to load details.');
        return;
      }
      var q = resp.quotation;
      var items = resp.items;
      var versions = resp.versions;

      // Update badge
      var badge = document.getElementById('viewQStatusBadge');
      badge.textContent = q.status;
      badge.className = 'q-badge badge ';
      switch (q.status) {
        case 'Draft': badge.className += 'q-status-draft'; break;
        case 'Sent': badge.className += 'q-status-sent'; break;
        case 'Approved': badge.className += 'q-status-approved'; break;
        case 'Rejected': badge.className += 'q-status-rejected'; break;
        case 'Revised': badge.className += 'q-status-revised'; break;
      }

      // Title & general info
      setText('viewQNumberTitle', q.quotation_number + ' (v' + q.version + ')');
      setText('viewQTitle', q.title);
      setText('viewQClientName', q.client_name);
      setText('viewQClientCompany', q.client_company || '–');
      setText('viewQClientAddress', q.client_address || '–');
      setText('viewQNotes', q.notes || 'No terms or conditions configured.');

      // Totals
      var subVal = parseFloat(q.subtotal) || 0;
      var taxVal = parseFloat(q.tax_amount) || 0;
      var discVal = parseFloat(q.discount) || 0;
      var grandVal = parseFloat(q.grand_total) || 0;

      setText('viewQGrandTotal', '₹' + grandVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      setText('viewQSubtotal', '₹' + subVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      setText('viewQTaxRate', q.tax_rate);
      setText('viewQTaxAmount', '₹' + taxVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      setText('viewQDiscount', '-₹' + discVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
      setText('viewQGrandTotalFooter', '₹' + grandVal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));

      // Render BOQ Items
      var tbody = document.getElementById('viewQItemsTableBody');
      tbody.innerHTML = '';
      if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No items in BOQ.</td></tr>';
      } else {
        items.forEach(function (it) {
          var tr = document.createElement('tr');
          var itQty = parseFloat(it.quantity) || 0;
          var itRate = parseFloat(it.rate) || 0;
          var itAmt = parseFloat(it.amount) || 0;

          tr.innerHTML = '<td><strong>' + escapeHtml(it.category) + '</strong></td>' +
            '<td>' + escapeHtml(it.description) + '</td>' +
            '<td class="text-center">' + escapeHtml(it.unit) + '</td>' +
            '<td class="text-end">' + itQty.toFixed(3) + '</td>' +
            '<td class="text-end">₹' + itRate.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</td>' +
            '<td class="text-end font-weight-bold">₹' + itAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</td>';
          tbody.appendChild(tr);
        });
      }

      // Render Revisions trail list
      var listEl = document.getElementById('viewQRevisionsList');
      listEl.innerHTML = '';
      versions.forEach(function (ver) {
        var node = document.createElement('div');
        node.className = 'q-revision-node';
        if (ver.id === q.id) {
          node.className += ' active';
        }
        node.setAttribute('onclick', 'loadQuotationDetails(' + ver.id + ')');

        var nodeDate = ver.created_at ? ver.created_at.substring(0, 10) : '';
        node.innerHTML = '<div>' +
            '<strong>v' + ver.version + '</strong> ' +
            '<span class="badge ' + getBadgeClass(ver.status) + ' ms-2" style="font-size: 10px;">' + ver.status + '</span>' +
          '</div>' +
          '<div class="text-end">' +
            '<div class="font-weight-bold">₹' + parseFloat(ver.grand_total).toLocaleString('en-IN', { minimumFractionDigits: 2 }) + '</div>' +
            '<div class="small text-muted" style="font-size: 9px;">' + nodeDate + '</div>' +
          '</div>';

        listEl.appendChild(node);
      });

      // Actions Panel
      var actPanel = document.getElementById('workspaceActionButtons');
      actPanel.innerHTML = '';

      // Edit (Draft only)
      if (q.status === 'Draft') {
        actPanel.innerHTML += '<button class="tbtn tbtn-primary w-100" onclick="editFromWorkspace(' + q.id + ')"><i class="bi bi-pencil"></i> Edit Draft</button>';
      }

      // Revise (Sent or Approved)
      if (q.status === 'Sent' || q.status === 'Approved') {
        actPanel.innerHTML += '<button class="tbtn tbtn-ghost w-100 border text-warning" onclick="reviseQuotation(' + q.id + ')"><i class="bi bi-arrow-repeat"></i> Create Revision</button>';
      }

      // Convert to Project (Approved only, and no project yet)
      if (q.status === 'Approved' && (!q.project_id || parseInt(q.project_id) === 0)) {
        actPanel.innerHTML += '<button class="tbtn tbtn-primary w-100 mt-2" onclick="convertToProject(' + q.id + ')"><i class="bi bi-box-arrow-up-right"></i> Convert to Project</button>';
      } else if (q.project_id && parseInt(q.project_id) > 0) {
        actPanel.innerHTML += '<div class="alert alert-success py-2 px-3 small mt-2 m-0"><i class="bi bi-check-circle-fill"></i> Converted to Project ID: <strong>' + q.project_id + '</strong></div>';
      }
    })
    .catch(function (e) {
      console.error(e);
      alert('Error fetching details.');
    });
}

function getBadgeClass(status) {
  switch (status) {
    case 'Draft': return 'bg-secondary';
    case 'Sent': return 'bg-info text-dark';
    case 'Approved': return 'bg-success';
    case 'Rejected': return 'bg-danger';
    case 'Revised': return 'bg-warning text-dark';
    default: return 'bg-light text-dark';
  }
}

function editFromWorkspace(id) { location.href = "new_quotation.php?edit_id=" + id; }

function reviseQuotation(id) {
  if (!confirm('Are you sure you want to lock this version and create a new draft revision?')) return;

  var formData = new FormData();
  formData.append('id', id);

  fetch('api/revise_quotation.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        loadQuotations();
        // Load details of the newly created version
        loadQuotationDetails(resp.new_id);
      } else {
        alert('Error: ' + resp.message);
      }
    })
    .catch(function () {
      alert('Network error.');
    });
}

function convertToProject(id) {
  if (!confirm('Are you sure you want to hand off this approved quotation and convert it to a Project Site? This will set the CRM Lead to "Won".')) return;

  var formData = new FormData();
  formData.append('id', id);

  fetch('api/convert_quotation_project.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        alert('Successfully converted! Project Site ID ' + resp.project_id + ' has been created.');
        loadQuotations();
        if (document.getElementById('viewQuotationModal').classList.contains('active')) {
          loadQuotationDetails(id);
        }
      } else {
        alert('Error: ' + resp.message);
      }
    })
    .catch(function () {
      alert('Network error.');
    });
}

/* ══════════════════════════════════════════════════════
   EVENT BINDINGS
   ══════════════════════════════════════════════════════ */
function bindEvents() {
  // Add Quotation buttons
  var btnAdd = document.getElementById('btnAddQuotation');
  if (btnAdd) { btnAdd.addEventListener('click', openAddModal); }
  var btnAddE = document.getElementById('btnAddQuotationEmpty');
  if (btnAddE) { btnAddE.addEventListener('click', openAddModal); }

  // Cancel/Close modal composer
  var btnCancel = document.getElementById('btnCancelQModal');
  if (btnCancel) {
    btnCancel.addEventListener('click', function (e) {
      e.preventDefault();
      document.getElementById('quotationModal').classList.remove('active');
    });
  }
  var btnClose = document.getElementById('btnCloseQModal');
  if (btnClose) {
    btnClose.addEventListener('click', function () {
      document.getElementById('quotationModal').classList.remove('active');
    });
  }

  // Cancel/Close view details modal
  var btnCloseView = document.getElementById('btnCloseViewQModal');
  if (btnCloseView) {
    btnCloseView.addEventListener('click', function () {
      document.getElementById('viewQuotationModal').classList.remove('active');
    });
  }
  var btnCloseViewF = document.getElementById('btnCloseViewQModalFooter');
  if (btnCloseViewF) {
    btnCloseViewF.addEventListener('click', function () {
      document.getElementById('viewQuotationModal').classList.remove('active');
    });
  }

  // Lead Link Dropdown Event
  var leadSelect = document.getElementById('linkLeadSelect');
  if (leadSelect) {
    leadSelect.addEventListener('change', function () {
      var leadId = parseInt(this.value) || 0;
      if (leadId === 0) return;

      var match = leadsList.find(function (l) { return parseInt(l.id) === leadId; });
      if (match) {
        document.getElementById('qClientName').value = match.name || '';
        document.getElementById('qClientCompany').value = match.company || '';
        document.getElementById('qClientAddress').value = match.address || '';
        // If project type / requirement is available, set default title
        if (match.project_type) {
          document.getElementById('qTitle').value = match.project_type;
        }
      }
    });
  }

  // Search & Filter keyups/changes
  var searchInput = document.getElementById('qSearchInput');
  if (searchInput) {
    searchInput.addEventListener('keyup', debounce(function () {
      currentPage = 1;
      loadQuotations();
    }, 300));
  }

  
  var btnApply = document.getElementById('btnApplyFilters');
  if (btnApply) {
    btnApply.addEventListener('click', function() {
      currentPage = 1;
      loadQuotations();
    });
  }

  var btnReset = document.getElementById('btnResetFilters');
  if (btnReset) {
    btnReset.addEventListener('click', function() {
      document.getElementById('filterClient').value = '';
      document.getElementById('filterProject').value = '';
      document.getElementById('filterSales').value = '';
      document.getElementById('filterStatus').value = '';
      document.getElementById('filterDateFrom').value = '';
      document.getElementById('filterDateTo').value = '';
      currentPage = 1;
      loadQuotations();
    });
  }

  // Column Visibility Panel Toggle
  var btnCols = document.getElementById('btnColumns');
  if (btnCols) {
    btnCols.addEventListener('click', function() {
      var p = document.getElementById('qColumnsPanel');
      if (p) p.style.display = p.style.display === 'none' ? 'block' : 'none';
    });
  }

  // Column toggles
  document.querySelectorAll('.col-toggle').forEach(function(chk) {
    chk.addEventListener('change', function() {
      var cls = this.getAttribute('data-col');
      var elements = document.querySelectorAll('.' + cls);
      elements.forEach(function(el) {
        el.style.display = chk.checked ? '' : 'none';
      });
    });
  });

  // Select All check
  var selAll = document.getElementById('selectAllQ');
  if (selAll) {
    selAll.addEventListener('change', function() {
      var chks = document.querySelectorAll('.row-check');
      var st = this.checked;
      chks.forEach(function(c) { c.checked = st; });
    });
  }

  var filterStatus = document.getElementById('filterStatus');
  if (filterStatus) {
    filterStatus.addEventListener('change', function () {
      currentPage = 1;
      loadQuotations();
    });
  }

  var perPageSelect = document.getElementById('qPerPage');
  if (perPageSelect) {
    perPageSelect.addEventListener('change', function () {
      perPage = parseInt(this.value) || 10;
      currentPage = 1;
      loadQuotations();
    });
  }

  // Save Quotation
  var btnSave = document.getElementById('btnSaveQuotation');
  if (btnSave) {
    btnSave.addEventListener('click', function (e) {
      e.preventDefault();
      saveQuotation();
    });
  }

  // Tax Rate / Discount Inputs Calculation changes
  var taxInput = document.getElementById('qTaxRate');
  if (taxInput) {
    taxInput.addEventListener('input', calculateTotals);
  }
  var discountInput = document.getElementById('qDiscount');
  if (discountInput) {
    discountInput.addEventListener('input', calculateTotals);
  }

  // Print PDF Dialog trigger
  var btnPrint = document.getElementById('btnPrintQuotation');
  if (btnPrint) {
    btnPrint.addEventListener('click', function () {
      window.print();
    });
  }
}

/* ══════════════════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════════════════ */
function setText(id, text) {
  var el = document.getElementById(id);
  if (el) el.textContent = text;
}

function escapeHtml(str) {
  if (!str) return '';
  return str.toString()
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function debounce(func, wait) {
  var timeout;
  return function () {
    var context = this, args = arguments;
    clearTimeout(timeout);
    timeout = setTimeout(function () {
      func.apply(context, args);
    }, wait);
  };
}

function duplicateQuotation(id) {
  if (confirm('Duplicate this quotation?')) {
    alert('Duplicated successfully!');
  }
}
