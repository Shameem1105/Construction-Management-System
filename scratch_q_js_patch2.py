import re

with open('js/quotations.js', 'r', encoding='utf-8') as f:
    content = f.read()

new_bind_events = """
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
"""

content = re.sub(
    r'(var filterStatus = document\.getElementById\(\'filterStatus\'\);)',
    new_bind_events + r'\n  \1',
    content
)

with open('js/quotations.js', 'w', encoding='utf-8') as f:
    f.write(content)

print("quotations.js event bindings patched")
