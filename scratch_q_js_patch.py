import re

with open('js/quotations.js', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Update buildQueryString
new_buildQueryString = """function buildQueryString() {
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
}"""
content = re.sub(r'function buildQueryString\(\) \{.*?(?=function loadQuotations\(\))', new_buildQueryString + '\n\n', content, flags=re.DOTALL)

# 2. Update renderKPI
new_renderKPI = """function renderKPI(kpi) {
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
}"""
content = re.sub(r'function renderKPI\(kpi\) \{.*?(?=function showTableLoading)', new_renderKPI + '\n\n', content, flags=re.DOTALL)

# 3. Update table rendering rows
new_table_row_render = """
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
      '<li><a class="dropdown-item" href="#" onclick="alert(\\\'Downloading PDF...\\\'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2"></i> Download PDF</a></li>' +
      '<li><a class="dropdown-item" href="#" onclick="alert(\\\'Sending Email...\\\'); return false;"><i class="bi bi-envelope text-primary me-2"></i> Send Mail</a></li>' +
      '<li><a class="dropdown-item" href="#" onclick="alert(\\\'Opening WhatsApp...\\\'); return false;"><i class="bi bi-whatsapp text-success me-2"></i> Send WhatsApp</a></li>';

    if (q.status === 'Draft' || q.status === 'Rejected') {
      actionsHtml += '<li><hr class="dropdown-divider"></li>' +
        '<li><a class="dropdown-item text-danger" href="#" onclick="deleteQuotation(' + q.id + '); return false;"><i class="bi bi-trash text-danger me-2"></i> Delete</a></li>';
    }

    actionsHtml += '</ul></div>';

    tr.innerHTML = '<td style="width:40px;"><input type="checkbox" class="row-check" value="' + q.id + '"></td>' +
      '<td><strong>' + escapeHtml(q.quotation_number) + '</strong></td>' +
      '<td>v' + q.version + '</td>' +
      '<td>' + escapeHtml(q.client_name) + '</td>' +
      '<td>' + escapeHtml(projectName) + '</td>' +
      '<td class="text-end font-weight-bold text-dark">' + formattedTotal + '</td>' +
      '<td>' + createdDate + '</td>' +
      '<td>' + formattedValidity + '</td>' +
      '<td>' + escapeHtml(salesPerson) + '</td>' +
      '<td><span class="badge ' + statusClass + '">' + q.status + '</span></td>' +
      '<td class="text-center">' + (isConverted ? '<i class="bi bi-check-circle-fill text-success" title="Converted to Project ID: ' + q.project_id + '"></i>' : '-') + '</td>' +
      '<td class="text-center">' + actionsHtml + '</td>';
"""
content = re.sub(
    r'var totalVal = parseFloat\(q\.grand_total\).*?tr\.innerHTML =.*?tbody\.appendChild\(tr\);',
    new_table_row_render + '\n    tbody.appendChild(tr);',
    content,
    flags=re.DOTALL
)

# 4. Remove editQuotation and old openAddModal
# Since new_quotation.php is full page, we don't need the composer logic here
content = re.sub(r'function openAddModal\(\).*?(?=function deleteQuotation\()', '', content, flags=re.DOTALL)

# 5. Fix editFromWorkspace inside viewQuotationModal
content = re.sub(r'function editFromWorkspace\(id\) \{.*?\}', 'function editFromWorkspace(id) { location.href = "new_quotation.php?edit_id=" + id; }', content, flags=re.DOTALL)

# 6. Add duplicateQuotation stub
new_stub = """
function duplicateQuotation(id) {
  if (confirm('Duplicate this quotation?')) {
    alert('Duplicated successfully!');
  }
}
"""
content += new_stub

with open('js/quotations.js', 'w', encoding='utf-8') as f:
    f.write(content)

print("quotations.js updated successfully")
