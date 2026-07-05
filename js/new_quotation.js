/**
 * new_quotation.js – Full-Page Quotation Composer
 * JGC Constructions ERP
 */

/* ══════════════════════════════════════════════════════
   STATE
   ══════════════════════════════════════════════════════ */
var leadsList = [];
var editMode = false;
var activeQuotationId = null;

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
  loadLeads();
  bindEvents();

  // Check if editing existing quotation
  var urlParams = new URLSearchParams(window.location.search);
  var editId = parseInt(urlParams.get('edit_id') || 0);
  if (editId > 0 || (typeof EDIT_QUOTATION_ID !== 'undefined' && EDIT_QUOTATION_ID > 0)) {
    var id = editId || EDIT_QUOTATION_ID;
    loadEditData(id);
  } else {
    // New quotation: initialize empty BOQ
    var container = document.getElementById('boqContainer');
    if (container) {
      DEFAULT_CATEGORIES.forEach(function (cat) {
        createCategorySection(cat);
        addBoqRow(cat);
      });
    }
    calculateTotals();

    // Pre-fill from lead if ?lead_id=X
    if (typeof PREFILL_LEAD_ID !== 'undefined' && PREFILL_LEAD_ID > 0) {
      document.getElementById('linkLeadSelect').value = PREFILL_LEAD_ID;
    }
  }
});

/* ══════════════════════════════════════════════════════
   LOAD LEADS FOR DROPDOWN
   ══════════════════════════════════════════════════════ */
function loadLeads() {
  fetch('api/get_leads.php?limit=200')
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        leadsList = resp.leads;
        populateLeadSelect();

        // If prefill lead id available and leads loaded, trigger autofill
        if (typeof PREFILL_LEAD_ID !== 'undefined' && PREFILL_LEAD_ID > 0) {
          var sel = document.getElementById('linkLeadSelect');
          if (sel) {
            sel.value = PREFILL_LEAD_ID;
            autofillFromLead(PREFILL_LEAD_ID);
          }
        }
      }
    })
    .catch(function () {
      console.warn('Failed to load CRM leads for dropdown.');
    });
}

function populateLeadSelect() {
  var select = document.getElementById('linkLeadSelect');
  if (!select) return;
  select.innerHTML = '<option value="">Select CRM Lead…</option>';
  leadsList.forEach(function (lead) {
    var opt = document.createElement('option');
    opt.value = lead.id;
    opt.textContent = lead.name + ' – ' + (lead.company || 'No Company') + ' (' + lead.phone + ')';
    select.appendChild(opt);
  });
}

function autofillFromLead(leadId) {
  var match = leadsList.find(function (l) { return parseInt(l.id) === parseInt(leadId); });
  if (match) {
    setValue('qClientName', match.name || '');
    setValue('qClientCompany', match.company || '');
    setValue('qClientAddress', match.address || '');
    if (match.project_type && !getValue('qTitle')) {
      setValue('qTitle', match.project_type);
    }
  }
}

/* ══════════════════════════════════════════════════════
   LOAD EDIT DATA (edit mode)
   ══════════════════════════════════════════════════════ */
function loadEditData(id) {
  editMode = true;
  activeQuotationId = id;
  document.getElementById('qEditId').value = id;
  document.getElementById('qStatusContainer').style.display = '';
  document.getElementById('nqBreadcrumbLabel').textContent = 'Edit Quotation';
  document.getElementById('btnSaveLabel').textContent = 'Save Changes';

  fetch('api/get_quotation_details.php?id=' + id)
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (!resp.success) { showToast('Failed to load quotation details', 'error'); return; }
      var q = resp.quotation;

      setValue('linkLeadSelect', q.lead_id || '');
      setValue('qClientName', q.client_name);
      setValue('qClientCompany', q.client_company || '');
      setValue('qClientAddress', q.client_address || '');
      setValue('qTitle', q.title);
      setValue('qStatus', q.status);
      setValue('qNotes', q.notes || '');
      setValue('qTaxRate', q.tax_rate);
      setValue('qDiscount', q.discount);

      // Rebuild BOQ
      var container = document.getElementById('boqContainer');
      container.innerHTML = '';
      var catsInDb = [];

      resp.items.forEach(function (item) {
        if (catsInDb.indexOf(item.category) === -1) {
          catsInDb.push(item.category);
          createCategorySection(item.category);
        }
        addBoqRow(item.category, item);
      });

      DEFAULT_CATEGORIES.forEach(function (cat) {
        if (catsInDb.indexOf(cat) === -1) {
          createCategorySection(cat);
          addBoqRow(cat);
        }
      });

      calculateTotals();
    })
    .catch(function () {
      showToast('Error loading quotation data', 'error');
    });
}

/* ══════════════════════════════════════════════════════
   BOQ BUILDER
   ══════════════════════════════════════════════════════ */
function createCategorySection(categoryName) {
  var container = document.getElementById('boqContainer');
  if (!container) return;

  var safeName = categoryName.replace(/[^a-zA-Z0-9]/g, '');

  var section = document.createElement('div');
  section.className = 'boq-category-block';
  section.id = 'boq-cat-block-' + safeName;

  var header = document.createElement('div');
  header.className = 'boq-section-header';
  header.innerHTML =
    '<span><i class="bi bi-folder2"></i> ' + escNQ(categoryName) + '</span>' +
    '<div class="d-flex gap-2">' +
      '<button type="button" class="btn btn-sm btn-outline-primary py-0" style="font-size:11px;" onclick="addBoqRow(\'' + escNQ(categoryName) + '\')">' +
        '<i class="bi bi-plus-lg"></i> Add Item' +
      '</button>' +
      '<button type="button" class="btn btn-sm btn-outline-danger py-0" style="font-size:11px;" onclick="removeCategorySection(\'' + safeName + '\')">' +
        '<i class="bi bi-trash"></i>' +
      '</button>' +
    '</div>';

  var tableWrap = document.createElement('div');
  tableWrap.className = 'table-responsive mt-1';

  var table = document.createElement('table');
  table.className = 'boq-grid-table';
  table.innerHTML =
    '<thead><tr>' +
      '<th style="width:50%;">Description / Specification</th>' +
      '<th style="width:10%;" class="text-center">Unit</th>' +
      '<th style="width:12%;" class="text-end">Quantity</th>' +
      '<th style="width:13%;" class="text-end">Unit Rate (₹)</th>' +
      '<th style="width:12%;" class="text-end">Amount (₹)</th>' +
      '<th style="width:3%;" class="text-center"></th>' +
    '</tr></thead>' +
    '<tbody id="boq-tbody-' + safeName + '"></tbody>';

  tableWrap.appendChild(table);
  section.appendChild(header);
  section.appendChild(tableWrap);
  container.appendChild(section);
}

function removeCategorySection(safeName) {
  var block = document.getElementById('boq-cat-block-' + safeName);
  if (block) {
    block.parentNode.removeChild(block);
    calculateTotals();
  }
}

function addBoqRow(category, itemData) {
  var safeName = category.replace(/[^a-zA-Z0-9]/g, '');
  var tbody = document.getElementById('boq-tbody-' + safeName);
  if (!tbody) return;

  var desc = itemData ? itemData.description : '';
  var unit = itemData ? itemData.unit : 'Nos';
  var qty  = itemData ? parseFloat(itemData.quantity) : 0;
  var rate = itemData ? parseFloat(itemData.rate) : 0;
  var amt  = qty * rate;

  var tr = document.createElement('tr');
  tr.className = 'boq-row-item';
  tr.setAttribute('data-category', category);

  tr.innerHTML =
    '<td><textarea class="boq-input boq-desc" style="height:48px;padding:4px;resize:vertical;" placeholder="Description/specification…">' + escNQ(desc) + '</textarea></td>' +
    '<td><input type="text" class="boq-input text-center boq-unit" value="' + escNQ(unit) + '" placeholder="e.g. Nos"></td>' +
    '<td><input type="number" class="boq-input text-end boq-qty" value="' + qty + '" step="0.001" min="0"></td>' +
    '<td><input type="number" class="boq-input text-end boq-rate" value="' + rate + '" step="0.01" min="0"></td>' +
    '<td><input type="text" class="boq-input text-end boq-amount" value="₹' + amt.toFixed(2) + '" readonly style="background:#f1f5f9;font-weight:600;"></td>' +
    '<td class="text-center"><button type="button" class="boq-row-del-btn" onclick="removeBoqRow(this)" title="Remove"><i class="bi bi-trash"></i></button></td>';

  var qtyInput  = tr.querySelector('.boq-qty');
  var rateInput = tr.querySelector('.boq-rate');
  var amtInput  = tr.querySelector('.boq-amount');

  var updateAmount = function () {
    var q = parseFloat(qtyInput.value) || 0;
    var r = parseFloat(rateInput.value) || 0;
    amtInput.value = '₹' + (q * r).toFixed(2);
    calculateTotals();
  };

  qtyInput.addEventListener('input', updateAmount);
  rateInput.addEventListener('input', updateAmount);

  tbody.appendChild(tr);
  calculateTotals();
}

function removeBoqRow(btn) {
  var tr = btn.closest('tr');
  if (tr) { tr.parentNode.removeChild(tr); calculateTotals(); }
}

function calculateTotals() {
  var subtotal = 0;
  document.querySelectorAll('.boq-row-item').forEach(function (tr) {
    var q = parseFloat(tr.querySelector('.boq-qty').value) || 0;
    var r = parseFloat(tr.querySelector('.boq-rate').value) || 0;
    subtotal += q * r;
  });

  var taxRate   = parseFloat(getValue('qTaxRate')) || 0;
  var taxAmount = (subtotal * taxRate) / 100;
  var discount  = parseFloat(getValue('qDiscount')) || 0;
  var grand     = subtotal + taxAmount - discount;

  setText('calcSubtotal',  '₹' + subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
  setText('calcTaxAmount', '₹' + taxAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
  setText('calcGrandTotal','₹' + grand.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
}

/* ══════════════════════════════════════════════════════
   ADD CATEGORY DIALOG
   ══════════════════════════════════════════════════════ */
function openAddCategoryDialog() {
  var overlay = document.getElementById('addCatOverlay');
  if (!overlay) {
    // Create dynamically
    overlay = document.createElement('div');
    overlay.className = 'nq-add-cat-overlay';
    overlay.id = 'addCatOverlay';
    overlay.innerHTML =
      '<div class="nq-add-cat-box">' +
        '<h6><i class="bi bi-folder-plus"></i> Add New Category</h6>' +
        '<div class="leads-field-icon-wrap mb-3">' +
          '<i class="bi bi-tag"></i>' +
          '<input type="text" id="newCatName" class="leads-input" placeholder="e.g. Roofing, Landscaping…">' +
        '</div>' +
        '<div class="d-flex gap-2 justify-content-end">' +
          '<button class="tbtn tbtn-ghost" onclick="closeAddCategoryDialog()">Cancel</button>' +
          '<button class="tbtn tbtn-primary" onclick="confirmAddCategory()"><i class="bi bi-plus-lg"></i> Add</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(overlay);
  }
  overlay.classList.add('active');
  var input = document.getElementById('newCatName');
  if (input) { input.value = ''; setTimeout(function(){ input.focus(); }, 100); }
}

function closeAddCategoryDialog() {
  var overlay = document.getElementById('addCatOverlay');
  if (overlay) overlay.classList.remove('active');
}

function confirmAddCategory() {
  var input = document.getElementById('newCatName');
  var name = (input ? input.value.trim() : '');
  if (!name) { input.focus(); return; }

  // Check duplicate
  if (document.getElementById('boq-cat-block-' + name.replace(/[^a-zA-Z0-9]/g, ''))) {
    showToast('Category "' + name + '" already exists', 'error');
    return;
  }

  createCategorySection(name);
  addBoqRow(name);
  closeAddCategoryDialog();
}

/* ══════════════════════════════════════════════════════
   SAVE QUOTATION
   ══════════════════════════════════════════════════════ */
function saveQuotation() {
  var title      = getValue('qTitle').trim();
  var clientName = getValue('qClientName').trim();

  if (!title)      { showToast('Project Title is required', 'error'); return; }
  if (!clientName) { showToast('Client Name is required', 'error'); return; }

  var items = [];
  var hasEmptyDesc = false;
  document.querySelectorAll('.boq-row-item').forEach(function (tr) {
    var cat  = tr.getAttribute('data-category');
    var desc = tr.querySelector('.boq-desc').value.trim();
    var unit = tr.querySelector('.boq-unit').value.trim();
    var qty  = parseFloat(tr.querySelector('.boq-qty').value) || 0;
    var rate = parseFloat(tr.querySelector('.boq-rate').value) || 0;

    if (desc === '') { hasEmptyDesc = true; }
    else { items.push({ category: cat, description: desc, unit: unit, quantity: qty, rate: rate }); }
  });

  if (hasEmptyDesc && items.length === 0) {
    showToast('Please add at least one BOQ item with a description', 'error');
    return;
  }

  var subtotal  = 0;
  items.forEach(function (it) { subtotal += it.quantity * it.rate; });
  var taxRate   = parseFloat(getValue('qTaxRate')) || 0;
  var taxAmount = (subtotal * taxRate) / 100;
  var discount  = parseFloat(getValue('qDiscount')) || 0;
  var grand     = subtotal + taxAmount - discount;

  var formData = new FormData();
  if (editMode && activeQuotationId) {
    formData.append('id', activeQuotationId);
    formData.append('status', getValue('qStatus') || 'Draft');
  }
  formData.append('lead_id',        getValue('linkLeadSelect'));
  formData.append('title',          title);
  formData.append('client_name',    clientName);
  formData.append('client_company', getValue('qClientCompany').trim());
  formData.append('client_address', getValue('qClientAddress').trim());
  formData.append('subtotal',       subtotal);
  formData.append('tax_rate',       taxRate);
  formData.append('tax_amount',     taxAmount);
  formData.append('discount',       discount);
  formData.append('grand_total',    grand);
  formData.append('notes',          getValue('qNotes').trim());
  formData.append('items_json',     JSON.stringify(items));

  var url = editMode ? 'api/update_quotation.php' : 'api/add_quotation.php';

  // Disable button
  var btn = document.getElementById('btnSaveNQ');
  if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…'; }

  fetch(url, { method: 'POST', body: formData })
    .then(function (r) { return r.json(); })
    .then(function (resp) {
      if (resp.success) {
        showToast((editMode ? 'Quotation updated!' : 'Quotation created: ' + resp.quotation_number), 'success');
        setTimeout(function () { location.href = 'quotations.php'; }, 1200);
      } else {
        showToast('Error: ' + (resp.message || 'Could not save'), 'error');
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> ' + document.getElementById('btnSaveLabel').textContent; }
      }
    })
    .catch(function () {
      showToast('Network error. Please try again.', 'error');
      if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> ' + document.getElementById('btnSaveLabel').textContent; }
    });
}

/* ══════════════════════════════════════════════════════
   EVENT BINDINGS
   ══════════════════════════════════════════════════════ */
function bindEvents() {
  // Save button
  var btnSave = document.getElementById('btnSaveNQ');
  if (btnSave) { btnSave.addEventListener('click', saveQuotation); }

  // Lead select autofill
  var leadSel = document.getElementById('linkLeadSelect');
  if (leadSel) {
    leadSel.addEventListener('change', function () {
      autofillFromLead(parseInt(this.value) || 0);
    });
  }

  // Add category button
  var btnCat = document.getElementById('btnAddCategory');
  if (btnCat) { btnCat.addEventListener('click', openAddCategoryDialog); }

  // Tax & Discount recalculate
  var taxInput = document.getElementById('qTaxRate');
  if (taxInput) { taxInput.addEventListener('input', calculateTotals); }
  var discInput = document.getElementById('qDiscount');
  if (discInput) { discInput.addEventListener('input', calculateTotals); }

  // Keyboard shortcut Ctrl+S = save
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
      e.preventDefault();
      saveQuotation();
    }
  });
}

/* ══════════════════════════════════════════════════════
   HELPERS
   ══════════════════════════════════════════════════════ */
function setValue(id, val) {
  var el = document.getElementById(id);
  if (el) el.value = val;
}
function getValue(id) {
  var el = document.getElementById(id);
  return el ? el.value : '';
}
function setText(id, text) {
  var el = document.getElementById(id);
  if (el) el.textContent = text;
}
function escNQ(str) {
  if (!str) return '';
  return str.toString()
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

var nqToastTimeout;
function showToast(msg, type) {
  var toast = document.getElementById('nqToast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'nqToast';
    toast.className = 'nq-toast';
    document.body.appendChild(toast);
  }
  toast.className = 'nq-toast ' + (type || '');
  toast.innerHTML = '<i class="bi bi-' + (type === 'error' ? 'exclamation-circle' : 'check-circle-fill') + '"></i> ' + msg;
  setTimeout(function() { toast.classList.add('show'); }, 10);
  clearTimeout(nqToastTimeout);
  nqToastTimeout = setTimeout(function() { toast.classList.remove('show'); }, 3500);
}
