import re

# 1. Update quotations.css
with open('css/quotations.css', 'r', encoding='utf-8') as f:
    css = f.read()

# Fix grid-template-columns for 6 cards
css = re.sub(r'grid-template-columns: repeat\(5, minmax\(0, 1fr\)\);', 'grid-template-columns: repeat(6, minmax(0, 1fr));', css)

# Fix print blank page issue completely
print_css = """
/* Print Invoice styling sheet */
@media print {
  @page {
    margin: 0;
    size: auto;
  }
  
  html, body {
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    height: auto !important;
    overflow: visible !important;
    background: #ffffff !important;
    color: #000000 !important;
    font-size: 11pt !important;
    box-sizing: border-box !important;
    position: static !important;
  }

  /* Hide EVERYTHING by default */
  body > * {
    display: none !important;
  }

  /* Show ONLY the modal content */
  .main,
  .main .quotations-shell,
  #viewQuotationModal {
    display: block !important;
    position: static !important;
    width: 100% !important;
    height: auto !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
    transform: none !important;
    box-shadow: none !important;
  }

  #viewQuotationModal .leads-modal-box {
    box-shadow: none !important;
    border: none !important;
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    padding: 10px !important;
    height: auto !important;
    overflow: visible !important;
    position: static !important;
    transform: none !important;
  }

  /* Format printable sheets */
  .workspace-shell {
    display: block !important;
    width: 100% !important;
  }

  .workspace-left {
    display: block !important;
    border: none !important;
    background: none !important;
    padding: 0 !important;
    margin: 0 !important;
    width: 100% !important;
    page-break-inside: auto !important;
  }

  /* Hide unwanted parts inside modal */
  .leads-modal-header,
  .leads-modal-footer,
  .workspace-right,
  .client-profile-avatar,
  .workspace-tabs {
    display: none !important;
  }

  /* Ensure rows flow normally */
  .workspace-panel-section {
    page-break-inside: avoid !important;
    margin-bottom: 20px !important;
    padding: 0 !important;
    border: none !important;
    box-shadow: none !important;
  }

  .client-profile-header {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    border-bottom: 2px solid #000 !important;
    padding-bottom: 15px !important;
    margin-bottom: 20px !important;
    page-break-after: avoid !important;
  }

  .boq-grid-table {
    width: 100% !important;
    border-collapse: collapse !important;
    page-break-inside: auto !important;
  }

  .boq-grid-table tr {
    page-break-inside: avoid !important;
    page-break-after: auto !important;
  }

  .boq-grid-table th {
    background: #eee !important;
    color: #000 !important;
    border-bottom: 2px solid #000 !important;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .boq-grid-table td {
    border-bottom: 1px solid #ccc !important;
  }
}
"""

css = re.sub(r'/\* Print Invoice styling sheet \*/\s*@media print \{.*\}', print_css, css, flags=re.DOTALL)

with open('css/quotations.css', 'w', encoding='utf-8') as f:
    f.write(css)

print("Updated css/quotations.css")
