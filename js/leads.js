/**
 * leads.js – Leads Management Page
 * JGC Constructions ERP
 * Full backend integration with MySQL via PHP API
 */

/* ══════════════════════════════════════════════════════
   STATE
   ══════════════════════════════════════════════════════ */
var leadsData     = [];
var filteredLeads = [];
var currentPage   = 1;
var perPage       = 10;
var totalLeads    = 0;
var activeLeadId  = null;
var editMode      = false;

/* ══════════════════════════════════════════════════════
   INIT
   ══════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', function () {
  loadDashboardStats();
  loadLeads();
  bindEvents();
});

/* ══════════════════════════════════════════════════════
   LOAD LEADS FROM LIVE BACKEND API
   ══════════════════════════════════════════════════════ */
function loadLeads() {
  showTableLoading(true);

  var q = (document.getElementById('leadsSearchInput') || {}).value || '';
  var statusVal = (document.getElementById('filterStatus') || {}).value || '';
  var sourceVal = (document.getElementById('filterSource') || {}).value || '';
  var ownerVal = (document.getElementById('filterOwner') || {}).value || '';

  var url = 'api/get_leads.php?' + 
            'page=' + currentPage + 
            '&limit=' + perPage + 
            '&status=' + encodeURIComponent(statusVal) + 
            '&source=' + encodeURIComponent(sourceVal) + 
            '&owner=' + encodeURIComponent(ownerVal) + 
            '&q=' + encodeURIComponent(q);

  fetch(url)
    .then(function (response) { return response.json(); })
    .then(function (resp) {
      if (resp.success) {
        leadsData = resp.leads || [];
        totalLeads = resp.total || 0;

        renderLeadsTable();
        populateOwnerFilter(resp.owners || []);
        
        // Show pagination info
        var start = (currentPage - 1) * perPage + 1;
        var end = Math.min(currentPage * perPage, totalLeads);
        if (totalLeads === 0) { start = 0; end = 0; }
        setText('leadsPaginationInfo', 'Showing ' + start + ' to ' + end + ' of ' + totalLeads + ' leads');
      } else {
        showError(resp.message || 'Failed to load leads.');
      }
    })
    .catch(function (err) {
      console.error(err);
      showError('Network error loading leads. Please try again.');
    })
    .finally(function () {
      showTableLoading(false);
    });
}

function loadDashboardStats() {
  fetch('api/get_crm_dashboard.php')
    .then(function (response) { return response.json(); })
    .then(function (resp) {
      if (resp.success) {
        renderKPI(resp.kpi);
        renderFunnel(resp.funnel, resp.kpi);
        renderDonutFromSources(resp.sources);
        renderLeadStages(resp.funnel);
        renderUpcomingFollowups(resp.followups);
        renderOwnerPerformance(resp.owners);
      }
    })
    .catch(function (err) {
      console.error('Error loading dashboard statistics:', err);
    });
}

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

  var html = '';
  leadsData.forEach(function (lead) {
    var tagsHtml = '';
    var priority = lead.priority || 'Medium';
    var tagClass = 'bg-secondary';
    if (priority === 'Hot') tagClass = 'bg-danger';
    else if (priority === 'High') tagClass = 'bg-warning text-dark';
    else if (priority === 'Medium') tagClass = 'bg-info text-dark';
    else if (priority === 'Low') tagClass = 'bg-success';

    tagsHtml = '<span class="badge ' + tagClass + ' me-1" style="font-size: 10px;">' + esc(priority) + '</span>';

    var initials = getInitials(lead.name);
    var avatarColor = '#B8860B';

    var followupCell = '–';
    if (lead.followup_date) {
      followupCell = '<div><strong>' + formatDate(lead.followup_date) + '</strong></div>';
    }

    var ownerInitials = lead.owner ? getInitials(lead.owner) : '–';
    var ownerHtml = '–';
    if (lead.owner) {
      ownerHtml = '<div class="d-flex align-items-center gap-2">' +
                    '<div class="leads-avatar" style="background-color: #555; color: #fff; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 10px;">' + esc(ownerInitials) + '</div>' +
                    '<span style="font-size: 13px;">' + esc(lead.owner) + '</span>' +
                  '</div>';
    }

    html += '<tr>' +
      '<td><input type="checkbox" class="lead-checkbox" data-id="' + lead.id + '"></td>' +
      '<td class="col-lead-id">' + esc(lead.lead_id) + '</td>' +
      '<td class="col-lead-name">' +
        '<div class="d-flex align-items-center gap-2">' +
          '<div class="leads-avatar" style="background-color: ' + avatarColor + '; color: #fff; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">' + esc(initials) + '</div>' +
          '<div>' +
            '<strong>' + esc(lead.name) + '</strong>' +
            '<div class="mt-1">' + tagsHtml + '</div>' +
          '</div>' +
        '</div>' +
      '</td>' +
      '<td class="col-company">' + esc(lead.company || '–') + '</td>' +
      '<td class="col-phone">' + esc(lead.phone) + '</td>' +
      '<td class="col-email">' + esc(lead.email || '–') + '</td>' +
      '<td class="col-source"><span class="source-badge ' + getSourceClass(lead.source) + '">' + esc(lead.source) + '</span></td>' +
      '<td class="col-status"><span class="status-badge ' + getStatusClass(lead.status) + '">' + esc(lead.status) + '</span></td>' +
      '<td class="col-owner">' + ownerHtml + '</td>' +
      '<td class="col-followup">' + followupCell + '</td>' +
      '<td class="col-actions text-center">' +
        '<div class="d-flex justify-content-center gap-1">' +
          '<button class="btn btn-sm btn-outline-primary" style="padding: 2px 6px; font-size: 12px;" onclick="viewLead(' + lead.id + ')" title="View Details"><i class="bi bi-eye"></i></button>' +
          '<button class="btn btn-sm btn-outline-warning" style="padding: 2px 6px; font-size: 12px;" onclick="editLead(' + lead.id + ')" title="Edit Lead"><i class="bi bi-pencil"></i></button>' +
          '<button class="btn btn-sm btn-outline-danger" style="padding: 2px 6px; font-size: 12px;" onclick="deleteLead(' + lead.id + ')" title="Delete Lead"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</td>' +
    '</tr>';
  });

  tbody.innerHTML = html;

  var totalPages = Math.ceil(totalLeads / perPage);
  renderPagination(totalPages);
}

/* ══════════════════════════════════════════════════════
   KPI CARDS
   ══════════════════════════════════════════════════════ */
function renderKPI(kpi) {
  if (!kpi) { return; }
  setText('kpiTotalLeads',  kpi.total            || 0);
  setText('kpiNewLeads',    kpi.new_leads        || 0);
  setText('kpiFollowup',    kpi.followup_pending || 0);
  setText('kpiSiteVisits',  kpi.site_visits      || 0);
  setText('kpiWon',         kpi.won              || 0);
  setText('kpiLost',        kpi.lost             || 0);

  // Set trend values dynamically
  setText('kpiTotalTrend',  kpi.total > 0 ? '+100%' : '0%');
  setText('kpiNewTrend',    kpi.new_leads > 0 ? '+100%' : '0%');
  setText('kpiFollowupTrend', kpi.followup_pending > 0 ? '+100%' : '0%');
  setText('kpiSiteVisitsTrend', kpi.site_visits > 0 ? '+100%' : '0%');
  setText('kpiWonTrend',    kpi.won > 0 ? '+100%' : '0%');
  setText('kpiLostTrend',   kpi.lost > 0 ? '+100%' : '0%');
}

/* ══════════════════════════════════════════════════════
   LEADS FUNNEL
   ══════════════════════════════════════════════════════ */
var FUNNEL_COLORS = {
  'New Lead': '#D4AF37',
  'Contacted': '#1d4ed8',
  'Meeting Scheduled': '#3b82f6',
  'Site Visit Scheduled': '#7c3aed',
  'Quotation Sent': '#06b6d4',
  'Negotiation': '#be185d',
  'Won': '#059669',
  'Lost': '#dc2626',
  'Hold': '#d97706',
  'Not Interested': '#64748b',
  'Duplicate': '#94a3b8'
};
var FUNNEL_ORDER = ['New Lead','Contacted','Meeting Scheduled','Site Visit Scheduled','Quotation Sent','Negotiation','Won','Lost','Hold','Not Interested','Duplicate'];

function renderFunnel(funnel, kpi) {
  var genVal   = document.getElementById('funnelGenVal');
  var qualVal  = document.getElementById('funnelQualVal');
  var quoteVal = document.getElementById('funnelQuoteVal');
  var negotVal = document.getElementById('funnelNegotVal');
  var wonVal   = document.getElementById('funnelWonVal');

  var genCount   = (funnel['New Lead'] || 0) + (funnel['New'] || 0);
  var qualCount  = funnel['Site Visit Scheduled'] || 0;
  var quoteCount = funnel['Quotation Sent'] || 0;
  var negotCount = funnel['Negotiation'] || 0;
  var wonCount   = funnel['Won'] || 0;

  if (genVal)   genVal.textContent   = genCount;
  if (qualVal)  qualVal.textContent  = qualCount;
  if (quoteVal) quoteVal.textContent = quoteCount;
  if (negotVal) negotVal.textContent = negotCount;
  if (wonVal)   wonVal.textContent   = wonCount;

  var convEl = document.getElementById('kpiConversionRate');
  var avgEl  = document.getElementById('kpiAvgConvTime');

  if (kpi) {
    if (convEl) convEl.textContent = kpi.conversion_rate || '0.00%';
    if (avgEl)  avgEl.textContent  = kpi.avg_conversion_time || '–';
  }
}

/* ══════════════════════════════════════════════════════
   DONUT CHART – SOURCE BREAKDOWN
   ══════════════════════════════════════════════════════ */
function renderDonutFromSources(sources) {
  var canvas  = document.getElementById('sourceDonutChart');
  var legEl   = document.getElementById('donutLegend');
  if (!canvas) { return; }

  var counts = {
    'Website': sources['Website'] || 0,
    'Referral': sources['Referral'] || 0,
    'Social Media': sources['Social Media'] || 0,
    'Walk-in': sources['Walk-in'] || 0,
    'Other': sources['Other'] || 0
  };

  Object.keys(sources).forEach(function (key) {
    if (counts[key] === undefined) {
      counts['Other'] += sources[key];
    }
  });

  var total = 0;
  var segments = [
    { label: 'Website', count: counts['Website'], color: '#D4AF37' },
    { label: 'Referral', count: counts['Referral'], color: '#059669' },
    { label: 'Social Media', count: counts['Social Media'], color: '#7c3aed' },
    { label: 'Walk-in', count: counts['Walk-in'], color: '#d97706' },
    { label: 'Other', count: counts['Other'], color: '#94a3b8' }
  ];

  segments.forEach(function (seg) {
    total += seg.count;
  });

  segments.forEach(function (seg) {
    seg.pct = total > 0 ? Math.round((seg.count / total) * 100) : 0;
  });

  var totalNumEl = document.querySelector('.donut-total-num');
  if (totalNumEl) {
    totalNumEl.textContent = total;
  }

  var ctx   = canvas.getContext('2d');
  var cx    = canvas.width  / 2;
  var cy    = canvas.height / 2;
  var outer = Math.min(cx, cy) - 4;
  var inner = outer * 0.55;
  var start = -Math.PI / 2;
  var gap   = 0.025;

  ctx.clearRect(0, 0, canvas.width, canvas.height);

  segments.forEach(function (seg) {
    if (seg.count === 0) return;
    var slice = total > 0 ? (seg.count / total) * (2 * Math.PI) - gap : 0;
    if (slice <= 0) return;

    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.arc(cx, cy, outer, start + gap / 2, start + gap / 2 + slice);
    ctx.closePath();
    ctx.fillStyle = seg.color;
    ctx.fill();
    start += slice + gap;
  });

  ctx.beginPath();
  ctx.arc(cx, cy, inner, 0, 2 * Math.PI);
  ctx.fillStyle = '#ffffff';
  ctx.fill();

  if (legEl) {
    var legHtml = '';
    segments.forEach(function (seg) {
      legHtml += '<div class="donut-legend-item">' +
        '<div class="donut-legend-left">' +
          '<span class="donut-swatch" style="background:' + seg.color + '"></span>' +
          '<span>' + seg.label + '</span>' +
        '</div>' +
        '<div>' +
          '<span class="donut-legend-pct">' + seg.pct + '%</span>' +
          '<span class="donut-legend-count">(' + seg.count + ')</span>' +
        '</div>' +
      '</div>';
    });
    legEl.innerHTML = legHtml;
  }
}

/* ══════════════════════════════════════════════════════
   LEAD STAGES
   ══════════════════════════════════════════════════════ */
function renderLeadStages(funnel) {
  var listEl = document.getElementById('leadStagesList');
  if (!listEl) { return; }

  var total = 0;
  FUNNEL_ORDER.forEach(function (status) {
    total += funnel[status] || 0;
  });
  if (total === 0) { total = 1; }

  var html = '';
  FUNNEL_ORDER.forEach(function (status) {
    var count = funnel[status] || 0;
    var pct = ((count / total) * 100).toFixed(1);
    var color = FUNNEL_COLORS[status] || '#94a3b8';

    html += '<div class="status-prog-row">' +
      '<div class="status-prog-label-wrap">' +
        '<span class="status-prog-name">' + status + '</span>' +
        '<span class="status-prog-count">' + count + ' <span class="status-prog-pct">(' + pct + '%)</span></span>' +
      '</div>' +
      '<div class="progress" style="height: 6px;">' +
        '<div class="progress-bar" role="progressbar" style="width: ' + pct + '%; background-color: ' + color + ';"></div>' +
      '</div>' +
    '</div>';
  });

  listEl.innerHTML = html;
}

/* ══════════════════════════════════════════════════════
   UPCOMING FOLLOW-UPS
   ══════════════════════════════════════════════════════ */
function renderUpcomingFollowups(followups) {
  var listEl = document.getElementById('upcomingFollowupsList');
  var countBadge = document.getElementById('upcomingFollowupsCount');
  if (!listEl) { return; }

  if (countBadge) {
    countBadge.textContent = followups.length;
  }

  if (followups.length === 0) {
    listEl.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:15px;text-align:center;">No upcoming follow-ups</div>';
    return;
  }

  var colors = ['date-red', 'date-blue', 'date-green', 'date-violet', 'date-sky'];
  var months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

  var html = '';
  followups.forEach(function (f, idx) {
    var fDate = new Date(f.followup_date);
    var day = fDate.getDate();
    var month = months[fDate.getMonth()] || 'MAY';
    var colorClass = colors[idx % colors.length];

    var timeClass = 'text-success';
    if (f.diff_days < 0) {
      timeClass = 'text-danger';
    } else if (f.diff_days <= 2) {
      timeClass = 'text-danger';
    } else if (f.diff_days <= 5) {
      timeClass = 'text-warning';
    }

    html += '<div class="followup-item-box">' +
      '<div class="followup-date-badge ' + colorClass + '">' +
        '<div class="f-day">' + day + '</div>' +
        '<div class="f-month">' + month + '</div>' +
      '</div>' +
      '<div class="followup-item-details" style="cursor: pointer;" onclick="viewLead(' + f.id + ')">' +
        '<div class="f-name">' + esc(f.name) + '</div>' +
        '<div class="f-company">' + esc(f.company || '–') + '</div>' +
        '<div class="f-time ' + timeClass + '">' + esc(f.relative_days) + '</div>' +
      '</div>' +
      '<div class="followup-item-actions">' +
        '<a href="tel:' + f.phone + '" class="followup-act-btn d-flex align-items-center justify-content-center" style="text-decoration:none; color:inherit;" aria-label="Call"><i class="bi bi-telephone"></i></a>' +
        '<a href="mailto:' + f.email + '" class="followup-act-btn d-flex align-items-center justify-content-center" style="text-decoration:none; color:inherit;" aria-label="Email"><i class="bi bi-envelope"></i></a>' +
      '</div>' +
    '</div>';
  });

  listEl.innerHTML = html;
}

/* ══════════════════════════════════════════════════════
   OWNER PERFORMANCE
   ══════════════════════════════════════════════════════ */
function renderOwnerPerformance(owners) {
  var listEl = document.getElementById('ownerPerformanceList');
  if (!listEl) { return; }

  if (owners.length === 0) {
    listEl.innerHTML = '<div style="color:#94a3b8;font-size:12px;padding:15px;text-align:center;">No owner performance data</div>';
    return;
  }

  var max = owners[0].count || 1;
  var colors = ['avatar-purple', 'avatar-teal', 'avatar-indigo', 'avatar-orange', 'avatar-pink'];
  var barColors = ['#D4AF37', '#17a2b8', '#7c3aed', '#d97706', '#e21b5a'];

  var html = '';
  owners.forEach(function (o, idx) {
    var pct = Math.round((o.count / max) * 100);
    var init = o.name.split(' ').map(function (w) { return w[0]; }).join('').toUpperCase().slice(0, 2);
    var avatarClass = colors[idx % colors.length];
    var barColor = barColors[idx % barColors.length];

    html += '<div class="owner-perf-item-box">' +
      '<div class="owner-perf-avatar ' + avatarClass + '">' + init + '</div>' +
      '<div class="owner-perf-body">' +
        '<div class="owner-perf-name-row">' +
          '<span class="owner-perf-name">' + esc(o.name) + '</span>' +
          '<span class="owner-perf-count">' + o.count + ' Leads</span>' +
        '</div>' +
        '<div class="progress" style="height: 5px;">' +
          '<div class="progress-bar" role="progressbar" style="width: ' + pct + '%; background-color: ' + barColor + ';"></div>' +
        '</div>' +
      '</div>' +
    '</div>';
  });

  listEl.innerHTML = html;
}

/* ══════════════════════════════════════════════════════
   POPULATE OWNER FILTER DROPDOWN
   ══════════════════════════════════════════════════════ */
function populateOwnerFilter(owners) {
  var filterSelect = document.getElementById('filterOwner');
  if (!filterSelect) return;

  var currentVal = filterSelect.value;
  var html = '<option value="">All Owners</option>';
  
  owners.forEach(function(o) {
    if (o) {
      var selected = (o === currentVal) ? ' selected' : '';
      html += '<option value="' + esc(o) + '"' + selected + '>' + esc(o) + '</option>';
    }
  });

  filterSelect.innerHTML = html;
}

/* ══════════════════════════════════════════════════════
   PAGINATION NAV
   ══════════════════════════════════════════════════════ */
function renderPagination(pages) {
  var navEl = document.getElementById('leadsPageNav');
  if (!navEl) { return; }

  var html = '<button class="leads-page-btn" id="pagePrev" ' + (currentPage <= 1 ? 'disabled' : '') + '><i class="bi bi-chevron-left"></i></button>';
  var s = Math.max(1, currentPage - 2);
  var e = Math.min(pages, s + 4);
  if (e - s < 4) { s = Math.max(1, e - 4); }
  for (var p = s; p <= e; p++) {
    html += '<button class="leads-page-btn ' + (p === currentPage ? 'active' : '') + '" data-page="' + p + '">' + p + '</button>';
  }
  html += '<button class="leads-page-btn" id="pageNext" ' + (currentPage >= pages ? 'disabled' : '') + '><i class="bi bi-chevron-right"></i></button>';
  navEl.innerHTML = html;

  navEl.querySelectorAll('[data-page]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      currentPage = parseInt(this.getAttribute('data-page'), 10);
      loadLeads();
    });
  });
  var prev = document.getElementById('pagePrev');
  var next = document.getElementById('pageNext');
  if (prev) { prev.addEventListener('click', function () { if (currentPage > 1) { currentPage--; loadLeads(); } }); }
  if (next) { next.addEventListener('click', function () { if (currentPage < pages) { currentPage++; loadLeads(); } }); }
}

/* ══════════════════════════════════════════════════════
   ADD / EDIT LEAD
   ══════════════════════════════════════════════════════ */
function openAddModal() {
  window.location.href = 'add_lead.php';
}

function editLead(id) {
  window.location.href = 'add_lead.php?id=' + id;
}

/* ══════════════════════════════════════════════════════
   DELETE LEAD
   ══════════════════════════════════════════════════════ */
function deleteLead(id) {
  if (!confirm('Are you sure you want to delete this lead?')) { return; }

  var formData = new FormData();
  formData.append('id', id);

  fetch('api/delete_lead.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        loadLeads();
        loadDashboardStats();
      } else {
        alert('Error: ' + (resp.message || 'Could not delete lead.'));
      }
    })
    .catch(function () { alert('Network error. Please try again.'); });
}

/* ══════════════════════════════════════════════════════
   VIEW LEAD MODAL & WORKSPACE SYSTEM
   ══════════════════════════════════════════════════════ */
function getActivityIcon(type) {
  var map = {
    'Lead Created': 'bi-person-plus text-primary',
    'Status Change': 'bi-circle-half text-warning',
    'Owner Assignment': 'bi-person-badge text-info',
    'Call': 'bi-telephone text-success',
    'Meeting': 'bi-people text-primary',
    'WhatsApp': 'bi-whatsapp text-success',
    'Email': 'bi-envelope text-primary',
    'Reminder': 'bi-bell text-warning',
    'Site Visit': 'bi-geo-alt text-danger'
  };
  return map[type] || 'bi-info-circle text-secondary';
}

function viewLead(id) {
  activeLeadId = id;
  
  var contentEl = document.getElementById('viewLeadContent');
  if (contentEl) {
    contentEl.innerHTML = '<div style="text-align:center;padding:48px;color:#94a3b8;">' +
      '<i class="bi bi-hourglass-split" style="font-size:24px;margin-bottom:12px;display:block;"></i>Loading workspace details…</div>';
  }
  
  var modalBox = document.querySelector('#viewLeadModal .leads-modal-box');
  if (modalBox) {
    modalBox.classList.remove('leads-modal-box-wide');
    modalBox.classList.add('leads-modal-box-workspace');
  }
  
  openModal('viewLeadModal');

  function renderDetailField(label, value) {
    return '<div class="lead-detail-item">' +
      '<div class="lead-detail-label">' + label + '</div>' +
      '<div class="lead-detail-value">' + (value || '–') + '</div>' +
    '</div>';
  }

  function getPriorityColor(priority) {
    var p = (priority || '').toLowerCase();
    if (p === 'high' || p === 'hot') return '#dc2626';
    if (p === 'medium') return '#d97706';
    return '#475569';
  }

  function renderLeadView(lead) {
    if (!contentEl) return;

    var init = getInitials(lead.name || 'L');

    var budgetStr = '–';
    var budget = (lead.budget !== undefined && lead.budget !== null) ? lead.budget : lead.estimated_budget;
    if (budget !== null && budget !== undefined && budget !== '') {
      var budgetVal = parseFloat(budget);
      if (!isNaN(budgetVal)) {
        budgetStr = '₹' + budgetVal.toLocaleString('en-IN');
      }
    }

    var expectedStartDate = lead.expected_start || lead.expected_start_date || lead.start_date || '–';
    if (expectedStartDate && expectedStartDate !== '–' && typeof expectedStartDate === 'string' && expectedStartDate.match(/^\\d{4}-\\d{2}-\\d{2}/)) {
      expectedStartDate = formatDate(expectedStartDate);
    }
    
    var followupDate = lead.followup_date || lead.next_followup_date;
    var nextFollowupStr = '–';
    if (followupDate) {
      nextFollowupStr = formatDate(followupDate);
    }

    var leadValueStr = '–';
    var leadValue = lead.lead_value || lead.value;
    if (leadValue !== null && leadValue !== undefined && leadValue !== '' && leadValue !== '–') {
      var leadValFloat = parseFloat(leadValue);
      if (!isNaN(leadValFloat)) {
        leadValueStr = '₹' + leadValFloat.toLocaleString('en-IN');
      }
    }

    var leadName = lead.name || '–';
    var company = lead.company || lead.company_name || 'Individual client';
    var status = lead.status || lead.lead_status || 'New';
    var source = lead.source || lead.lead_source || 'Other';
    var priority = lead.priority || 'Medium';
    var owner = lead.owner || lead.assigned_to || '–';
    var phone = lead.phone || '–';
    var altPhone = lead.alternate_phone || lead.alt_phone || '–';
    var email = lead.email || '–';
    var address = lead.address || '–';
    var city = lead.city || '–';
    var state = lead.state || '–';
    var pincode = lead.pincode || '–';
    var projectType = lead.project_type || lead.requirement_type || '–';
    var requirement = lead.requirement || lead.project_requirement || '–';
    var preferredContact = lead.preferred_contact_method || lead.preferred_contact || '–';
    var currentStage = lead.stage || lead.current_stage || status || '–';

    contentEl.innerHTML = '<div class="workspace-shell">' +
      '<div class="workspace-left" style="text-align: center; align-items: center; justify-content: center; width: 100%; display: flex; flex-direction: column;">' +
        '<div class="client-profile-header" style="border-bottom: none; padding-bottom: 0; width: 100%;">' +
          '<div class="client-profile-avatar" style="width: 80px; height: 80px; font-size: 28px; background: linear-gradient(135deg, #FFF4CC, #FFD700); color: #B8860B; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin: 0 auto 12px; box-shadow: 0 4px 10px rgba(212, 175, 55, 0.2);">' + init + '</div>' +
          '<div class="client-profile-name" style="font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1.3; margin-top: 12px; margin-bottom: 6px; word-break: break-word;">' + esc(leadName) + '</div>' +
          '<div class="client-profile-company" style="font-size: 13px; color: #64748b; font-weight: 500; margin-bottom: 16px; word-break: break-word;">' + esc(company) + '</div>' +
          '<span class="status-badge ' + getStatusClass(status) + '" style="font-size: 12px; padding: 6px 14px; border-radius: 20px; font-weight: 700; display: inline-block;">' + esc(status) + '</span>' +
        '</div>' +
      '</div>' +
      
      '<div class="workspace-right" style="display: flex; flex-direction: column; gap: 20px;">' +
        '<div class="lead-details-section">' +
          '<h6><i class="bi bi-info-circle text-gold"></i> Basic Information</h6>' +
          '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px 20px;">' +
            renderDetailField('Lead ID', esc(lead.lead_id || '–')) +
            renderDetailField('Status', '<span class="status-badge ' + getStatusClass(status) + '">' + esc(status) + '</span>') +
            renderDetailField('Lead Source', '<span class="source-badge ' + getSourceClass(source) + '">' + esc(source) + '</span>') +
            renderDetailField('Priority', '<span class="priority-badge" style="font-weight: 700; color: ' + getPriorityColor(priority) + ';"><i class="bi bi-flag-fill"></i> ' + esc(priority) + '</span>') +
            renderDetailField('Assigned Owner', esc(owner)) +
          '</div>' +
        '</div>' +

        '<div class="lead-details-section">' +
          '<h6><i class="bi bi-telephone text-gold"></i> Contact Information</h6>' +
          '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px 20px;">' +
            renderDetailField('Phone', '<i class="bi bi-telephone text-gold"></i> ' + esc(phone)) +
            renderDetailField('Alternate Phone', esc(altPhone)) +
            renderDetailField('Email', '<i class="bi bi-envelope text-gold"></i> ' + esc(email)) +
            renderDetailField('Address', esc(address)) +
            renderDetailField('City', esc(city)) +
            renderDetailField('State', esc(state)) +
            renderDetailField('Pincode', esc(pincode)) +
          '</div>' +
        '</div>' +

        '<div class="lead-details-section">' +
          '<h6><i class="bi bi-briefcase text-gold"></i> Business Information</h6>' +
          '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px 20px;">' +
            renderDetailField('Project Type', esc(projectType)) +
            renderDetailField('Estimated Budget', budgetStr) +
            renderDetailField('Expected Start Date', expectedStartDate) +
            renderDetailField('Lead Value', leadValueStr) +
            renderDetailField('Requirement', esc(requirement)) +
            renderDetailField('Preferred Contact Method', esc(preferredContact)) +
            renderDetailField('Next Follow-up Date', nextFollowupStr) +
            renderDetailField('Current Stage', esc(currentStage)) +
          '</div>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  fetch('api/get_lead_details.php?id=' + id)
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (!resp.success) {
        if (contentEl) {
          contentEl.innerHTML = '<div style="color:#dc2626;padding:24px;text-align:center;">' + esc(resp.message) + '</div>';
        }
        return;
      }
      renderLeadView(resp.lead);
    })
    .catch(function () {
      if (contentEl) {
        contentEl.innerHTML = '<div style="color:#dc2626;padding:24px;text-align:center;">Network error loading details.</div>';
      }
    });
}

function vFieldDetail(label, value) {
  return '<div class="profile-field">' +
    '<div class="profile-field-label">' + label + '</div>' +
    '<div class="profile-field-value">' + (value || '–') + '</div>' +
  '</div>';
}

// Global Tab switcher
window.switchWorkspaceTab = function (evt, tabId) {
  var parent = evt.currentTarget.closest('.workspace-right');
  if (!parent) return;

  parent.querySelectorAll('.workspace-tab-btn').forEach(function (btn) {
    btn.classList.remove('active');
  });

  parent.querySelectorAll('.workspace-tab-content').forEach(function (content) {
    content.classList.remove('active');
  });

  evt.currentTarget.classList.add('active');

  var targetContent = parent.querySelector('#' + tabId);
  if (targetContent) {
    targetContent.classList.add('active');
  }
};

// Global Follow-up recorder
window.saveFollowup = function (evt, leadId) {
  evt.preventDefault();
  var form = evt.target;
  var formData = new FormData(form);
  formData.append('lead_id', leadId);

  var btn = form.querySelector('button[type="submit"]');
  if (btn) { btn.disabled = true; btn.textContent = 'Saving...'; }

  fetch('api/add_lead_followup.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        viewLead(leadId);
        loadLeads();
        loadDashboardStats();
      } else {
        alert('Error: ' + (resp.message || 'Could not record follow-up.'));
      }
    })
    .catch(function () { alert('Network error. Please try again.'); })
    .finally(function () {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Follow-up'; }
    });
};

// Global Site Visit recorder
window.saveSiteVisit = function (evt, leadId) {
  evt.preventDefault();
  var form = evt.target;
  var formData = new FormData(form);
  formData.append('lead_id', leadId);

  var btn = form.querySelector('button[type="submit"]');
  if (btn) { btn.disabled = true; btn.textContent = 'Saving...'; }

  fetch('api/add_lead_site_visit.php', { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        viewLead(leadId);
        loadLeads();
        loadDashboardStats();
      } else {
        alert('Error: ' + (resp.message || 'Could not record site visit.'));
      }
    })
    .catch(function () { alert('Network error. Please try again.'); })
    .finally(function () {
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Save Site Visit'; }
    });
};

/* ══════════════════════════════════════════════════════
   ROW ACTIONS
   ══════════════════════════════════════════════════════ */
function callLead(phone, evt) {
  if (evt) evt.stopPropagation();
  if (phone) { window.location.href = 'tel:' + phone.replace(/\\s/g, ''); }
}

function emailLead(email, evt) {
  if (evt) evt.stopPropagation();
  if (email) { window.location.href = 'mailto:' + email; }
}

/* ══════════════════════════════════════════════════════
   MODAL ACTIONS
   ══════════════════════════════════════════════════════ */
function openModal(id) {
  var el = document.getElementById(id);
  if (el) { el.classList.add('active'); document.body.style.overflow = 'hidden'; }
}

function closeModal(id) {
  var el = document.getElementById(id);
  if (el) { el.classList.remove('active'); document.body.style.overflow = ''; }
}

/* ══════════════════════════════════════════════════════
   FILTER + SEARCH
   ══════════════════════════════════════════════════════ */
function applyFilters() {
  currentPage = 1;
  loadLeads();
}

function resetFilters() {
  var s = document.getElementById('filterStatus');
  var src = document.getElementById('filterSource');
  var own = document.getElementById('filterOwner');
  var q = document.getElementById('leadsSearchInput');

  if (s) s.value = '';
  if (src) src.value = '';
  if (own) own.value = '';
  if (q) q.value = '';

  applyFilters();
}

/* ══════════════════════════════════════════════════════
   LOADING STATE
   ══════════════════════════════════════════════════════ */
function showTableLoading(on) {
  var tbody = document.getElementById('leadsTableBody');
  if (!tbody) { return; }
  if (on) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:32px;color:#94a3b8;"><i class="bi bi-hourglass-split" style="font-size:20px;margin-bottom:8px;display:block;"></i>Loading leads…</td></tr>';
  }
}

function showError(msg) {
  var tbody = document.getElementById('leadsTableBody');
  if (tbody) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:32px;color:#dc2626;">' + msg + '</td></tr>';
  }
}

/* ══════════════════════════════════════════════════════
   EVENT BINDINGS
   ══════════════════════════════════════════════════════ */
function bindEvents() {
  /* Add Lead buttons */
  var btnAdd = document.getElementById('btnAddLead');
  if (btnAdd) { btnAdd.addEventListener('click', openAddModal); }
  var btnAddE = document.getElementById('btnAddLeadEmpty');
  if (btnAddE) { btnAddE.addEventListener('click', openAddModal); }

  /* Import Lead buttons */
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
            details = '\\n\\nIssues encountered:\\n' + resp.errors.join('\\n');
          }
          alert('Import completed successfully.\\nImported: ' + resp.imported_count + '\\nFailed: ' + resp.failed_count + details);
          loadLeads();
          loadDashboardStats();
        } else {
          alert('Import failed: ' + (resp.message || 'Unknown error'));
        }
      })
      .catch(function() {
        alert('Network error while importing leads.');
      })
      .finally(function() {
        btnImport.disabled = false;
        btnImport.innerHTML = '<i class="bi bi-download"></i> Import Leads';
      });
    });
  }

  var btnCloseV  = document.getElementById('btnCloseViewModal');
  if (btnCloseV) { btnCloseV.addEventListener('click', function () { closeModal('viewLeadModal'); }); }
  var btnCloseVF = document.getElementById('btnCloseViewModalFooter');
  if (btnCloseVF) { btnCloseVF.addEventListener('click', function () { closeModal('viewLeadModal'); }); }
  var btnEdit = document.getElementById('btnEditFromView');
  if (btnEdit) {
    btnEdit.addEventListener('click', function () {
      closeModal('viewLeadModal');
      if (activeLeadId) { editLead(activeLeadId); }
    });
  }

  /* Click outside modal */
  document.querySelectorAll('.leads-modal').forEach(function (m) {
    m.addEventListener('click', function (e) { if (e.target === m) { closeModal(m.id); } });
  });

  /* Search + Filters */
  var searchEl = document.getElementById('leadsSearchInput');
  if (searchEl) { searchEl.addEventListener('input', debounce(applyFilters, 350)); }

  ['filterStatus','filterSource','filterOwner'].forEach(function (id) {
    var el = document.getElementById(id);
    if (el) { el.addEventListener('change', applyFilters); }
  });

  /* Per page */
  var ppEl = document.getElementById('leadsPerPage');
  if (ppEl) {
    ppEl.addEventListener('change', function () {
      perPage = parseInt(this.value, 10);
      currentPage = 1;
      loadLeads();
    });
  }

  /* Escape key */
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') { return; }
    if (document.getElementById('viewLeadModal').classList.contains('active')) { closeModal('viewLeadModal'); }
  });
}

/* ══════════════════════════════════════════════════════
   UTILITIES
   ══════════════════════════════════════════════════════ */
function setText(id, val) {
  var el = document.getElementById(id);
  if (el) { el.textContent = val; }
}

function getVal(id) {
  var el = document.getElementById(id);
  return el ? el.value : '';
}

function setVal(id, val) {
  var el = document.getElementById(id);
  if (el) { el.value = val || ''; }
}

function getStatusClass(status) {
  var map = {
    'New Lead': 'st-new',
    'Contacted': 'st-contacted',
    'Meeting Scheduled': 'st-meeting',
    'Site Visit Scheduled': 'st-sitevisit',
    'Quotation Sent': 'st-quotation',
    'Negotiation': 'st-negotiation',
    'Won': 'st-won',
    'Lost': 'st-lost',
    'Hold': 'st-hold',
    'Not Interested': 'st-notinterested',
    'Duplicate': 'st-duplicate'
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

function getInitials(name) {
  if (!name) { return '?'; }
  return name.split(' ').map(function (w) { return w[0]; }).join('').toUpperCase().slice(0, 2);
}

function formatDate(dateStr) {
  if (!dateStr) return '–';
  var parts = dateStr.split('-');
  if (parts.length === 3) {
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var y = parts[0];
    var m = months[parseInt(parts[1], 10) - 1] || 'May';
    var d = parseInt(parts[2], 10);
    return d + ' ' + m + ' ' + y;
  }
  return dateStr;
}

function esc(str) {
  if (!str) { return ''; }
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function debounce(fn, ms) {
  var timer;
  return function () {
    clearTimeout(timer);
    timer = setTimeout(fn, ms);
  };
}
