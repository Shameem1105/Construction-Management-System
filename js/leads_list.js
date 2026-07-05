/**
 * leads_list.js – Dedicated Leads Module UI Logic
 * JGC Constructions ERP
 * Fetches live data from api/get_leads.php and api/get_lead_details.php
 */

/* ══════════════════════════════════════════════════════
   STATE MANAGEMENT
   ══════════════════════════════════════════════════════ */
var leadsData         = [];
var totalLeads        = 0;
var currentPage       = 1;
var perPage           = 10;
var selectedLeadId    = null;

var currentTabFilter   = 'all'; // 'all', 'New Lead', 'Follow-up', 'Qualified', 'Won', 'Lost'
var currentSourceFilter = '';
var currentOwnerFilter  = '';
var currentSearchQuery  = '';

/* ══════════════════════════════════════════════════════
   INITIALIZATION
   ══════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
  loadLeads();
  bindEvents();
});

/* ══════════════════════════════════════════════════════
   LOAD DATA FROM API
   ══════════════════════════════════════════════════════ */
function loadLeads() {
  showTableLoading(true);

  // Map quick status tabs to backend statuses
  // Tabs: All Leads, New, Follow-up, Qualified, Won, Lost
  var statusParam = '';
  if (currentTabFilter !== 'all') {
    statusParam = currentTabFilter;
  }

  var url = 'api/get_leads.php?' + 
            'page=' + currentPage + 
            '&limit=' + perPage + 
            '&status=' + encodeURIComponent(statusParam) + 
            '&source=' + encodeURIComponent(currentSourceFilter) + 
            '&owner=' + encodeURIComponent(currentOwnerFilter) + 
            '&q=' + encodeURIComponent(currentSearchQuery);

  fetch(url)
    .then(function (response) { return response.json(); })
    .then(function (resp) {
      if (resp.success) {
        leadsData = resp.leads || [];
        totalLeads = resp.total || 0;

        renderKPI(resp.kpi);
        renderTabBadges(resp.kpi);
        renderLeadsTable();
        populateOwnerDropdown(resp.owners || []);

        // Default: Load details of the first lead in the list if no lead is currently selected
        if (leadsData.length > 0 && selectedLeadId === null) {
          selectLead(leadsData[0].id);
        } else if (leadsData.length > 0) {
          // Check if selected lead is still in the new list to keep highlight
          var stillExists = leadsData.some(function (l) { return l.id === selectedLeadId; });
          if (stillExists) {
            selectLead(selectedLeadId);
          } else {
            selectLead(leadsData[0].id);
          }
        } else {
          clearDetailsPanel();
        }
      } else {
        showError(resp.message || 'Failed to load leads.');
      }
    })
    .catch(function (err) {
      showError('Network error loading leads. Please try again.');
    })
    .finally(function () {
      showTableLoading(false);
    });
}

/* ══════════════════════════════════════════════════════
   RENDER KPI STAT CARDS
   ══════════════════════════════════════════════════════ */
function renderKPI(kpi) {
  if (!kpi) return;
  setText('kpiTotalLeads',  kpi.total            || 0);
  setText('kpiNewLeads',    kpi.new_leads        || 0);
  setText('kpiFollowup',    kpi.followup_pending || 0);
  setText('kpiSiteVisits',  kpi.site_visits      || 0);
  setText('kpiWon',         kpi.won              || 0);
  setText('kpiLost',        kpi.lost             || 0);
}

/* ══════════════════════════════════════════════════════
   RENDER TABS BADGES WITH LIVE COUNTS
   ══════════════════════════════════════════════════════ */
function renderTabBadges(kpi) {
  if (!kpi) return;
  
  // Set tab badges
  setTabBadgeText('tabBadgeAll', kpi.total);
  setTabBadgeText('tabBadgeNew', kpi.new_leads);
  setTabBadgeText('tabBadgeFollowup', kpi.followup_pending);
  setTabBadgeText('tabBadgeQualified', kpi.site_visits);
  setTabBadgeText('tabBadgeWon', kpi.won);
  setTabBadgeText('tabBadgeLost', kpi.lost);
}

function setTabBadgeText(id, count) {
  var el = document.getElementById(id);
  if (el) {
    el.textContent = count || 0;
  }
}

/* ══════════════════════════════════════════════════════
   RENDER TABLE ROWS
   ══════════════════════════════════════════════════════ */
function renderLeadsTable() {
  var tbody = document.getElementById('leadsTableBody');
  var emptyState = document.getElementById('leadsEmptyState');
  var infoEl = document.getElementById('leadsPaginationInfo');
  
  if (!tbody) return;

  if (leadsData.length === 0) {
    tbody.innerHTML = '';
    if (emptyState) emptyState.style.display = 'block';
    if (infoEl) infoEl.textContent = 'Showing 0 to 0 of 0 leads';
    var navEl = document.getElementById('leadsPageNav');
    if (navEl) navEl.innerHTML = '';
    return;
  }

  if (emptyState) emptyState.style.display = 'none';

  var start = (currentPage - 1) * perPage;
  var end = Math.min(start + perPage, totalLeads);

  if (infoEl) {
    infoEl.textContent = 'Showing ' + (start + 1) + ' to ' + end + ' of ' + totalLeads + ' leads';
  }

  var html = '';
  leadsData.forEach(function (lead) {
    // Priority badge tags: New, Hot, Warm, Cold
    var priority = lead.priority || 'Medium';
    var tagClass = 'bg-secondary';
    if (priority === 'Hot') tagClass = 'bg-danger';
    else if (priority === 'High') tagClass = 'bg-warning text-dark';
    else if (priority === 'Medium') tagClass = 'bg-info text-dark';
    else if (priority === 'Low') tagClass = 'bg-success';

    var tagsHtml = '<span class="badge ' + tagClass + ' ms-2" style="font-size: 10px;">' + esc(priority) + '</span>';
    var initials = getInitials(lead.name);
    var avatarColor = getAvatarColor(lead.id);

    // Formatted Follow-up Date and Relative days
    var followupCell = '–';
    if (lead.followup_date) {
      followupCell = '<div><strong>' + formatDate(lead.followup_date) + '</strong></div>' +
                     '<div>' + getRelativeDays(lead.followup_date) + '</div>';
    }

    // Owner layout circular avatar
    var ownerInitials = lead.owner ? getInitials(lead.owner) : '–';
    var ownerHtml = '–';
    if (lead.owner) {
      ownerHtml = '<div class="d-flex align-items-center gap-2">' +
                    '<div class="leads-avatar" style="background-color: #555; color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 10px;">' + esc(ownerInitials) + '</div>' +
                    '<span style="font-size: 13px;">' + esc(lead.owner) + '</span>' +
                  '</div>';
    }

    var selectedClass = (lead.id === selectedLeadId) ? ' class="selected-row"' : '';

    html += '<tr id="lead-row-' + lead.id + '"' + selectedClass + ' onclick="selectLead(' + lead.id + ')">' +
      '<td onclick="event.stopPropagation()"><input type="checkbox" class="lead-checkbox" data-id="' + lead.id + '"></td>' +
      '<td class="col-lead-id">' + esc(lead.lead_id) + '</td>' +
      '<td class="col-lead-name">' +
        '<div class="d-flex align-items-center gap-2">' +
          '<div class="leads-avatar" style="background-color: ' + avatarColor + '; color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">' + esc(initials) + '</div>' +
          '<div>' +
            '<strong>' + esc(lead.name) + '</strong>' + tagsHtml +
          '</div>' +
        '</div>' +
      '</td>' +
      '<td class="col-company">' + esc(lead.company || '–') + '</td>' +
      '<td class="col-phone">' + esc(lead.phone) + '</td>' +
      '<td class="col-status"><span class="status-badge ' + getStatusClass(lead.status) + '">' + esc(lead.status) + '</span></td>' +
      '<td class="col-source"><span class="source-badge ' + getSourceClass(lead.source) + '">' + esc(lead.source) + '</span></td>' +
      '<td class="col-owner">' + ownerHtml + '</td>' +
      '<td class="col-followup">' + followupCell + '</td>' +
      '<td class="col-actions text-center" onclick="event.stopPropagation()">' +
        '<div class="d-flex justify-content-center gap-1">' +
          '<button class="icon-act-btn" onclick="callLead(\'' + lead.phone + '\', event)" title="Call lead"><i class="bi bi-telephone"></i></button>' +
          '<button class="icon-act-btn" onclick="emailLead(\'' + lead.email + '\', event)" title="Email lead"><i class="bi bi-envelope"></i></button>' +
          '<div class="dropdown d-inline-block">' +
            '<button class="icon-act-btn" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots-vertical"></i></button>' +
            '<ul class="dropdown-menu dropdown-menu-end">' +
              '<li><a class="dropdown-item" href="add_lead.php?id=' + lead.id + '"><i class="bi bi-pencil me-2"></i> Edit</a></li>' +
              '<li><hr class="dropdown-divider"></li>' +
              '<li><a class="dropdown-item text-danger" href="#" onclick="deleteLead(' + lead.id + ', event)"><i class="bi bi-trash me-2"></i> Delete</a></li>' +
            '</ul>' +
          '</div>' +
        '</div>' +
      '</td>' +
    '</tr>';
  });

  tbody.innerHTML = html;

  var totalPages = Math.ceil(totalLeads / perPage);
  renderPagination(totalPages);
}

/* ══════════════════════════════════════════════════════
   RENDER PAGINATION CONTROLS
   ══════════════════════════════════════════════════════ */
function renderPagination(pages) {
  var navEl = document.getElementById('leadsPageNav');
  if (!navEl) return;

  var html = '';
  // First and Previous buttons
  html += '<button class="leads-page-btn" id="pageFirst" ' + (currentPage <= 1 ? 'disabled' : '') + '><i class="bi bi-chevron-double-left"></i></button>';
  html += '<button class="leads-page-btn" id="pagePrev" ' + (currentPage <= 1 ? 'disabled' : '') + '><i class="bi bi-chevron-left"></i></button>';

  var startPage = Math.max(1, currentPage - 2);
  var endPage = Math.min(pages, startPage + 4);
  if (endPage - startPage < 4) {
    startPage = Math.max(1, endPage - 4);
  }

  for (var p = startPage; p <= endPage; p++) {
    html += '<button class="leads-page-btn ' + (p === currentPage ? 'active' : '') + '" data-page="' + p + '">' + p + '</button>';
  }

  // Next and Last buttons
  html += '<button class="leads-page-btn" id="pageNext" ' + (currentPage >= pages ? 'disabled' : '') + '><i class="bi bi-chevron-right"></i></button>';
  html += '<button class="leads-page-btn" id="pageLast" ' + (currentPage >= pages ? 'disabled' : '') + '><i class="bi bi-chevron-double-right"></i></button>';

  navEl.innerHTML = html;

  // Add click handlers
  navEl.querySelectorAll('[data-page]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      currentPage = parseInt(this.getAttribute('data-page'), 10);
      loadLeads();
    });
  });

  var firstBtn = document.getElementById('pageFirst');
  if (firstBtn) {
    firstBtn.addEventListener('click', function () {
      if (currentPage > 1) {
        currentPage = 1;
        loadLeads();
      }
    });
  }

  var prevBtn = document.getElementById('pagePrev');
  if (prevBtn) {
    prevBtn.addEventListener('click', function () {
      if (currentPage > 1) {
        currentPage--;
        loadLeads();
      }
    });
  }

  var nextBtn = document.getElementById('pageNext');
  if (nextBtn) {
    nextBtn.addEventListener('click', function () {
      if (currentPage < pages) {
        currentPage++;
        loadLeads();
      }
    });
  }

  var lastBtn = document.getElementById('pageLast');
  if (lastBtn) {
    lastBtn.addEventListener('click', function () {
      if (currentPage < pages) {
        currentPage = pages;
        loadLeads();
      }
    });
  }
}

/* ══════════════════════════════════════════════════════
   SELECT AND LOAD DYNAMIC LEAD DETAILS
   ══════════════════════════════════════════════════════ */
function selectLead(id) {
  selectedLeadId = id;

  // Set visual row selection highlights
  document.querySelectorAll('#leadsTableBody tr').forEach(function (row) {
    row.classList.remove('selected-row');
  });
  var selectedRow = document.getElementById('lead-row-' + id);
  if (selectedRow) {
    selectedRow.classList.add('selected-row');
  }

  // Load details in side panel
  var bodyEl = document.getElementById('panelDetailsBody');
  var placeholderEl = document.getElementById('panelPlaceholder');
  if (placeholderEl) placeholderEl.style.display = 'none';
  if (bodyEl) {
    bodyEl.style.display = 'block';
    bodyEl.innerHTML = '<div style="text-align:center;padding:48px;color:#94a3b8;">' +
      '<i class="bi bi-hourglass-split" style="font-size:24px;margin-bottom:12px;display:block;"></i>Loading details…</div>';
  }

  fetch('api/get_lead_details.php?id=' + id)
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success && bodyEl) {
        renderLeadDetailPanel(resp);
      } else if (bodyEl) {
        bodyEl.innerHTML = '<div class="text-danger text-center p-3">' + esc(resp.message || 'Error loading details.') + '</div>';
      }
    })
    .catch(function () {
      if (bodyEl) {
        bodyEl.innerHTML = '<div class="text-danger text-center p-3">Network error loading details.</div>';
      }
    });
}

function renderLeadDetailPanel(resp) {
  var lead = resp.lead;
  var bodyEl = document.getElementById('panelDetailsBody');
  if (!bodyEl || !lead) return;

  // Header Details
  var headerStatus = document.getElementById('panelHeaderStatus');
  if (headerStatus) {
    headerStatus.className = 'status-badge ' + getStatusClass(lead.status);
    headerStatus.textContent = lead.status || 'New';
  }

  var initials = getInitials(lead.name);
  var budgetVal = '–';
  if (lead.budget) {
    var floatVal = parseFloat(lead.budget);
    if (!isNaN(floatVal)) {
      budgetVal = '₹' + floatVal.toLocaleString('en-IN');
    }
  }

  // Render Action Icon link attributes
  var cleanPhone = lead.phone ? lead.phone.replace(/[^\d]/g, '') : '';
  var mailLink = lead.email ? 'mailto:' + lead.email : '#';
  var telLink = lead.phone ? 'tel:' + lead.phone.replace(/\s/g, '') : '#';
  var waLink = cleanPhone ? 'https://wa.me/' + cleanPhone : '#';

  // Activities Timeline HTML
  var activitiesHtml = '–';
  if (resp.activities && resp.activities.length > 0) {
    activitiesHtml = '<div class="timeline-track">';
    resp.activities.forEach(function (act) {
      var dateStr = act.created_at ? formatDateTime(act.created_at) : '–';
      var extra = act.user_name ? ' by ' + act.user_name : '';
      var itemClass = (act.activity_type && act.activity_type.includes('Follow-up')) ? 'timeline-item scheduled' : 'timeline-item';
      
      activitiesHtml += '<div class="' + itemClass + '">' +
                          '<div class="timeline-title">' + esc(act.activity_type || 'Activity Logged') + '</div>' +
                          '<div class="timeline-meta">' + esc(act.description || '') + ' ' + dateStr + extra + '</div>' +
                        '</div>';
    });
    activitiesHtml += '</div>';
  }

  bodyEl.innerHTML = 
    '<div class="profile-block">' +
      '<div class="profile-avatar">' + esc(initials) + '</div>' +
      '<div class="profile-name">' + esc(lead.name) + '</div>' +
      '<div class="profile-company">' + esc(lead.company || 'Individual client') + '</div>' +
      
      // Square Action Buttons Row
      '<div class="action-icons-row mt-3">' +
        '<a class="action-circle-btn" href="' + telLink + '" title="Call Client"><i class="bi bi-telephone"></i></a>' +
        '<a class="action-circle-btn" href="' + mailLink + '" title="Email Client"><i class="bi bi-envelope"></i></a>' +
        '<a class="action-circle-btn" href="' + waLink + '" target="_blank" title="WhatsApp Message"><i class="bi bi-whatsapp"></i></a>' +
        '<a class="action-circle-btn" href="#" title="Schedule Event"><i class="bi bi-calendar-event"></i></a>' +
      '</div>' +
    '</div>' +

    // Info Attributes Grid
    '<div class="detail-section mt-3">' +
      '<div class="detail-row"> <span class="detail-label">Lead ID</span> <span class="detail-val">' + esc(lead.lead_id) + '</span> </div>' +
      '<div class="detail-row"> <span class="detail-label">Lead Owner</span> <span class="detail-val">' + esc(lead.owner || '–') + '</span> </div>' +
      '<div class="detail-row"> <span class="detail-label">Source</span> <span class="detail-val"><span class="source-badge ' + getSourceClass(lead.source) + '">' + esc(lead.source || 'Other') + '</span></span> </div>' +
      '<div class="detail-row"> <span class="detail-label">Status</span> <span class="detail-val"><span class="status-badge ' + getStatusClass(lead.status) + '">' + esc(lead.status || 'New') + '</span></span> </div>' +
      '<div class="detail-row"> <span class="detail-label">Follow-up Date</span> <span class="detail-val"><strong>' + formatDate(lead.followup_date) + '</strong> ' + getRelativeDays(lead.followup_date) + '</span> </div>' +
      '<div class="detail-row"> <span class="detail-label">Created On</span> <span class="detail-val">' + formatDateTime(lead.created_at) + '</span> </div>' +
    '</div>' +

    // Contact details section
    '<div class="detail-section mt-3">' +
      '<div class="detail-section-title"><i class="bi bi-telephone text-gold"></i> Contact Details</div>' +
      '<div class="detail-row"> <span class="detail-label">Phone</span> <span class="detail-val">' + esc(lead.phone) + '</span> </div>' +
      '<div class="detail-row"> <span class="detail-label">Email</span> <span class="detail-val">' + esc(lead.email || '–') + '</span> </div>' +
      '<div class="detail-row"> <span class="detail-label">Address</span> <span class="detail-val">' + esc(lead.address || '–') + '</span> </div>' +
    '</div>' +

    // Requirements Section
    '<div class="detail-section mt-3">' +
      '<div class="detail-section-title"><i class="bi bi-building-add text-gold"></i> Requirements</div>' +
      '<div class="detail-row" style="flex-direction:column; align-items:flex-start; gap:4px;">' +
        '<span class="detail-label">Project Type / Budget</span>' +
        '<span class="detail-val" style="text-align:left; font-weight:700; max-width:100%;">' + esc(lead.project_type || '–') + ' (' + budgetVal + ')</span>' +
      '</div>' +
    '</div>' +

    // Notes Section
    '<div class="detail-section mt-3">' +
      '<div class="detail-section-title"><i class="bi bi-journal-text text-gold"></i> Notes</div>' +
      '<div class="detail-row" style="flex-direction:column; align-items:flex-start; gap:4px;">' +
        '<span class="detail-val" style="text-align:left; font-weight:500; color:#475569; max-width:100%; white-space:pre-line;">' + esc(lead.notes || 'No notes added.') + '</span>' +
      '</div>' +
    '</div>' +

    // Activities Section
    '<div class="detail-section mt-3">' +
      '<div class="detail-section-title"><i class="bi bi-clock-history text-gold"></i> Activities</div>' +
      activitiesHtml +
    '</div>' +

    // Edit Button
    '<div class="mt-4">' +
      '<a href="add_lead.php?id=' + lead.id + '" class="tbtn tbtn-primary w-100 justify-content-center" style="box-shadow: 0 4px 12px rgba(212,175,55,0.25);"><i class="bi bi-pencil"></i> Edit Lead</a>' +
    '</div>';
}

function clearDetailsPanel() {
  selectedLeadId = null;
  var bodyEl = document.getElementById('panelDetailsBody');
  var placeholderEl = document.getElementById('panelPlaceholder');
  if (bodyEl) bodyEl.style.display = 'none';
  if (placeholderEl) placeholderEl.style.display = 'flex';
}

/* ══════════════════════════════════════════════════════
   DELETE OPERATIONS
   ══════════════════════════════════════════════════════ */
function deleteLead(id, event) {
  if (event) event.stopPropagation();

  if (!confirm('Are you sure you want to delete this lead?')) return;

  var formData = new FormData();
  formData.append('id', id);

  fetch('api/delete_lead.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        if (selectedLeadId === id) {
          selectedLeadId = null;
        }
        loadLeads();
      } else {
        alert('Error: ' + (resp.message || 'Could not delete lead.'));
      }
    })
    .catch(function () {
      alert('Network error deleting lead.');
    });
}

/* ══════════════════════════════════════════════════════
   EVENT HANDLING AND LISTENERS
   ══════════════════════════════════════════════════════ */
function bindEvents() {
  // 1. Search Query Box
  var searchInput = document.getElementById('leadsSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', debounce(function () {
      currentSearchQuery = this.value;
      currentPage = 1;
      loadLeads();
    }, 350));
  }

  // 2. Quick Filter Tabs
  document.querySelectorAll('.quick-filter-tabs .q-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      document.querySelectorAll('.quick-filter-tabs .q-tab').forEach(function (t) {
        t.classList.remove('active');
      });
      this.classList.add('active');
      
      currentTabFilter = this.getAttribute('data-filter');
      currentPage = 1;
      loadLeads();
    });
  });

  // 3. Dropdowns (Status, Source, Owner Filters)
  var filterSource = document.getElementById('filterSource');
  if (filterSource) {
    filterSource.addEventListener('change', function () {
      currentSourceFilter = this.value;
      currentPage = 1;
      loadLeads();
    });
  }

  var filterOwner = document.getElementById('filterOwner');
  if (filterOwner) {
    filterOwner.addEventListener('change', function () {
      currentOwnerFilter = this.value;
      currentPage = 1;
      loadLeads();
    });
  }

  // 4. Per page dropdown
  var perPageSelect = document.getElementById('leadsPerPage');
  if (perPageSelect) {
    perPageSelect.addEventListener('change', function () {
      perPage = parseInt(this.value, 10);
      currentPage = 1;
      loadLeads();
    });
  }

  // 5. Select All Checkbox
  var selectAll = document.getElementById('selectAllCheckbox');
  if (selectAll) {
    selectAll.addEventListener('change', function () {
      var checked = this.checked;
      document.querySelectorAll('#leadsTableBody .lead-checkbox').forEach(function (cb) {
        cb.checked = checked;
      });
    });
  }

  // 6. Close details panel btn
  var closePanelBtn = document.getElementById('btnCloseDetailsPanel');
  if (closePanelBtn) {
    closePanelBtn.addEventListener('click', function () {
      clearDetailsPanel();
      // Remove row highlights
      document.querySelectorAll('#leadsTableBody tr').forEach(function (row) {
        row.classList.remove('selected-row');
      });
    });
  }

  // 7. Leads Import Trigger
  var btnImport = document.getElementById('btnImportLeads');
  var fileInput = document.getElementById('leadsImportInput');
  if (btnImport && fileInput) {
    btnImport.addEventListener('click', function () {
      fileInput.value = '';
      fileInput.click();
    });
    
    fileInput.addEventListener('change', function () {
      var file = fileInput.files[0];
      if (!file) return;
      
      var ext = file.name.split('.').pop().toLowerCase();
      if (ext !== 'csv' && ext !== 'xlsx') {
        alert('Please select a valid CSV or XLSX file.');
        return;
      }
      
      btnImport.disabled = true;
      btnImport.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Importing...';
      
      var fd = new FormData();
      fd.append('file', file);
      
      fetch('api/import_leads.php', {
        method: 'POST',
        body: fd
      })
      .then(function(r) { return r.json(); })
      .then(function(resp) {
        if (resp.success) {
          var details = '';
          if (resp.errors && resp.errors.length > 0) {
            details = '\n\nIssues encountered:\n' + resp.errors.join('\n');
          }
          alert('Import completed successfully.\nImported: ' + resp.imported_count + '\nFailed: ' + resp.failed_count + details);
          loadLeads();
        } else {
          alert('Import failed: ' + (resp.message || 'Unknown error'));
        }
      })
      .catch(function() {
        alert('Network error while importing leads.');
      })
      .finally(function() {
        btnImport.disabled = false;
        btnImport.innerHTML = '<i class="bi bi-upload"></i> Import';
      });
    });
  }

  // 8. Server-side Export triggers
  var csvBtn = document.getElementById('exportCSVBtn');
  if (csvBtn) {
    csvBtn.addEventListener('click', function (e) {
      e.preventDefault();
      var statusParam = currentTabFilter === 'all' ? '' : currentTabFilter;
      var url = 'api/export_leads.php?format=csv' +
                '&status=' + encodeURIComponent(statusParam) +
                '&source=' + encodeURIComponent(currentSourceFilter) +
                '&owner=' + encodeURIComponent(currentOwnerFilter) +
                '&q=' + encodeURIComponent(currentSearchQuery);
      window.open(url, '_blank');
    });
  }

  var excelBtn = document.getElementById('exportExcelBtn');
  if (excelBtn) {
    excelBtn.addEventListener('click', function (e) {
      e.preventDefault();
      var statusParam = currentTabFilter === 'all' ? '' : currentTabFilter;
      var url = 'api/export_leads.php?format=excel' +
                '&status=' + encodeURIComponent(statusParam) +
                '&source=' + encodeURIComponent(currentSourceFilter) +
                '&owner=' + encodeURIComponent(currentOwnerFilter) +
                '&q=' + encodeURIComponent(currentSearchQuery);
      window.open(url, '_blank');
    });
  }
}

/* ══════════════════════════════════════════════════════
   CLIENT-SIDE CSV EXPORT
   ══════════════════════════════════════════════════════ */
function exportLeadsCSV() {
  if (leadsData.length === 0) {
    alert('No lead data to export.');
    return;
  }

  var csvContent = 'data:text/csv;charset=utf-8,';
  // Headers
  csvContent += 'Lead ID,Name,Company,Phone,Email,Status,Source,Owner,Follow-up Date,Notes\n';

  leadsData.forEach(function (lead) {
    var row = [
      lead.lead_id || '',
      lead.name || '',
      lead.company || '',
      lead.phone || '',
      lead.email || '',
      lead.status || '',
      lead.source || '',
      lead.owner || '',
      lead.followup_date || '',
      (lead.notes || '').replace(/"/g, '""').replace(/\n/g, ' ')
    ];
    csvContent += row.map(function (val) { return '"' + val + '"'; }).join(',') + '\n';
  });

  var encodedUri = encodeURI(csvContent);
  var link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', 'JGC_CRM_Leads_' + new Date().toISOString().slice(0, 10) + '.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

/* ══════════════════════════════════════════════════════
   DYNAMIC OWNER DROPDOWN POPULATOR
   ══════════════════════════════════════════════════════ */
function populateOwnerDropdown(owners) {
  var select = document.getElementById('filterOwner');
  if (!select) return;

  // Keep the first option ("All Owners")
  var currentVal = select.value;
  select.innerHTML = '<option value="">All Owners</option>';
  
  owners.forEach(function (owner) {
    if (owner) {
      var opt = document.createElement('option');
      opt.value = owner;
      opt.textContent = owner;
      if (owner === currentVal) {
        opt.selected = true;
      }
      select.appendChild(opt);
    }
  });
}

/* ══════════════════════════════════════════════════════
   UTILITY HELPERS
   ══════════════════════════════════════════════════════ */
function callLead(phone, event) {
  if (event) event.stopPropagation();
  if (phone) window.location.href = 'tel:' + phone.replace(/\s/g, '');
}

function emailLead(email, event) {
  if (event) event.stopPropagation();
  if (email && email !== '–') window.location.href = 'mailto:' + email;
}

function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map(function (w) { return w[0]; }).join('').toUpperCase().slice(0, 2);
}

function getAvatarColor(id) {
  var colors = ['#7c3aed', '#475569', '#3b82f6', '#0ea5e9', '#0d9488', '#0f766e', '#B8860B', '#d97706'];
  return colors[id % colors.length];
}

function getRelativeDays(dateStr) {
  if (!dateStr) return '';
  var date = new Date(dateStr);
  var today = new Date();
  
  date.setHours(0,0,0,0);
  today.setHours(0,0,0,0);
  
  var diffTime = date.getTime() - today.getTime();
  var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays === 0) return '<span style="color: #d97706; font-size: 11px; font-weight: 700; margin-left: 4px;">Today</span>';
  if (diffDays === 1) return '<span style="color: #d97706; font-size: 11px; font-weight: 700; margin-left: 4px;">Tomorrow</span>';
  if (diffDays > 1) return '<span style="color: #059669; font-size: 11px; font-weight: 700; margin-left: 4px;">in ' + diffDays + ' days</span>';
  if (diffDays < 0) return '<span style="color: #dc2626; font-size: 11px; font-weight: 700; margin-left: 4px;">' + Math.abs(diffDays) + ' days ago</span>';
  return '';
}

function getStatusClass(status) {
  var map = {
    'New Lead': 'st-new',
    'New': 'st-new',
    'Contacted': 'st-contacted',
    'Meeting Scheduled': 'st-meeting',
    'Site Visit Scheduled': 'st-meeting',
    'Quotation Sent': 'st-quotation',
    'Negotiation': 'st-negotiation',
    'Won': 'st-won',
    'Lost': 'st-lost',
    'Hold': 'st-hold',
    'Not Interested': 'st-lost',
    'Duplicate': 'st-lost'
  };
  return map[status] || 'st-new';
}

function getSourceClass(source) {
  var map = {
    'Website': 'sb-website',
    'Referral': 'sb-referral',
    'Social Media': 'sb-social',
    'Walk-in': 'sb-walkin',
    'Other': 'sb-other'
  };
  return map[source] || 'sb-other';
}

function formatDate(dateStr) {
  if (!dateStr) return '–';
  var date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  
  var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
}

function formatDateTime(dateStr) {
  if (!dateStr) return '–';
  var date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  
  var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var hours = date.getHours();
  var minutes = date.getMinutes();
  var ampm = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12;
  hours = hours ? hours : 12; // the hour '0' should be '12'
  minutes = minutes < 10 ? '0' + minutes : minutes;
  
  return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear() + ', ' + hours + ':' + minutes + ' ' + ampm;
}

function setText(id, val) {
  var el = document.getElementById(id);
  if (el) { el.textContent = val; }
}

function esc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function showTableLoading(on) {
  var tbody = document.getElementById('leadsTableBody');
  if (!tbody) return;
  if (on) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:48px;color:#94a3b8;">' +
      '<i class="bi bi-hourglass-split" style="font-size:24px;margin-bottom:12px;display:block;"></i>Loading leads…</td></tr>';
  }
}

function showError(msg) {
  var tbody = document.getElementById('leadsTableBody');
  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:48px;color:#dc2626;">' + esc(msg) + '</td></tr>';
  }
}

function debounce(fn, ms) {
  var timer;
  return function () {
    var self = this;
    var args = arguments;
    clearTimeout(timer);
    timer = setTimeout(function () {
      fn.apply(self, args);
    }, ms);
  };
}

function resetFilters() {
  var filterSource = document.getElementById('filterSource');
  if (filterSource) filterSource.value = '';
  var filterOwner = document.getElementById('filterOwner');
  if (filterOwner) filterOwner.value = '';
  var searchInput = document.getElementById('leadsSearchInput');
  if (searchInput) searchInput.value = '';
  
  currentSourceFilter = '';
  currentOwnerFilter = '';
  currentSearchQuery = '';
  currentPage = 1;
  
  loadLeads();
}
