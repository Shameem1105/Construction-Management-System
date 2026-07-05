(() => {
  const state = {
    rows: [],
    filteredRows: [],
    page: 1,
    limit: 10,
    activeType: "income",
    editingId: null,
    charts: {
      incomeExpense: null,
      incomeCategory: null,
      expenseCategory: null,
      cashFlow: null
    }
  };

  const els = {
    summaryCards: document.getElementById("summaryCards"),
    filterFromDate: document.getElementById("filterFromDate"),
    filterToDate: document.getElementById("filterToDate"),
    filterProject: document.getElementById("filterProject"),
    addEntryBtn: document.getElementById("addEntryBtn"),
    entryActionToggle: document.getElementById("entryActionToggle"),
    entryActionMenu: document.getElementById("entryActionMenu"),
    excelImportInput: document.getElementById("excelImportInput"),
    transactionSearch: document.getElementById("transactionSearch"),
    clearFiltersBtn: document.getElementById("clearFiltersBtn"),
    entryForm: document.getElementById("entryForm"),
    entryId: document.getElementById("entryId"),
    entryType: document.getElementById("entryType"),
    entryProject: document.getElementById("entryProject"),
    entryCategory: document.getElementById("entryCategory"),
    entryDate: document.getElementById("entryDate"),
    entryAmount: document.getElementById("entryAmount"),
    entryPaymentMethod: document.getElementById("entryPaymentMethod"),
    entryPartyName: document.getElementById("entryPartyName"),
    entryDescription: document.getElementById("entryDescription"),
    entryReceipt: document.getElementById("entryReceipt"),
    partyLabel: document.getElementById("partyLabel"),
    saveEntryBtn: document.getElementById("saveEntryBtn"),
    resetEntryBtn: document.getElementById("resetEntryBtn"),
    transactionTableBody: document.getElementById("transactionTableBody"),
    paginationWrap: document.getElementById("paginationWrap"),
    tableMeta: document.getElementById("tableMeta"),
    viewModal: document.getElementById("viewModal"),
    closeViewModalBtn: document.getElementById("closeViewModalBtn"),
    viewModalTitle: document.getElementById("viewModalTitle"),
    viewModalBody: document.getElementById("viewModalBody"),
    cashSummary: document.getElementById("cashSummary"),
    addEntryButtons: document.querySelectorAll("[data-type]"),
    incomeExpenseChart: document.getElementById("incomeExpenseChart"),
    incomeCategoryChart: document.getElementById("incomeCategoryChart"),
    expenseCategoryChart: document.getElementById("expenseCategoryChart"),
    cashFlowChart: document.getElementById("cashFlowChart")
  };

  const fallbackIncomeCategories = ["Client Payment", "Advance Received", "Material Return", "Other Income"];
  const fallbackExpenseCategories = ["Labour", "Material", "Transport", "Equipment", "Site Expense", "Others"];

  const currency = new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 0
  });

  function escapeHtml(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#39;");
  }

  function formatCurrency(value) {
    return currency.format(Number(value || 0));
  }

  function formatDate(value) {
    if (!value) return "-";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString("en-GB");
  }

  function formatTrend(trend) {
    if (trend === null || trend === undefined || Number.isNaN(Number(trend))) {
      return `<span class="metric-trend neutral"><i class="bi bi-dash"></i> --</span>`;
    }

    const value = Number(trend);
    const direction = value >= 0 ? "positive" : "negative";
    const icon = value >= 0 ? "bi-arrow-up" : "bi-arrow-down";
    return `<span class="metric-trend ${direction}"><i class="bi ${icon}"></i> ${Math.abs(value).toFixed(1)}%</span>`;
  }

  function buildSparkline(values, colorClass = "") {
    const series = Array.isArray(values) ? values.map((value) => Number(value) || 0) : [];
    const normalized = series.length ? series : [0, 0, 0, 0];
    const width = 180;
    const height = 36;
    const padding = 4;
    const max = Math.max(...normalized, 1);
    const min = Math.min(...normalized, 0);
    const range = max - min || 1;
    const step = normalized.length > 1 ? (width - padding * 2) / (normalized.length - 1) : 0;

    const points = normalized.map((value, index) => {
      const x = padding + index * step;
      const y = height - padding - ((value - min) / range) * (height - padding * 2);
      return `${x},${y}`;
    });

    const path = points.length
      ? `M ${points.join(" L ")}`
      : "M 0 20 L 180 20";

    return `
      <svg viewBox="0 0 ${width} ${height}" preserveAspectRatio="none" aria-hidden="true">
        <path d="${path}" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
      </svg>
    `;
  }

  function buildChartLabels(series) {
    return Array.isArray(series) ? series.map((item) => item.date) : [];
  }

  function populateProjects() {
    const projects = Array.isArray(window.JGC_PROJECTS) ? window.JGC_PROJECTS : [];
    const options = ['<option value="all">All Projects</option>'];
    const entryOptions = ['<option value="">Select project</option>'];

    projects.forEach((project) => {
      const option = `<option value="${escapeHtml(project.id)}">${escapeHtml(project.title)}</option>`;
      options.push(option);
      entryOptions.push(option);
    });

    if (els.filterProject) {
      els.filterProject.innerHTML = options.join("");
    }

    if (els.entryProject) {
      els.entryProject.innerHTML = entryOptions.join("");
    }
  }

  function populateCategories(type) {
    const categories = type === "expense"
      ? (Array.isArray(window.JGC_EXPENSE_CATEGORIES) && window.JGC_EXPENSE_CATEGORIES.length ? window.JGC_EXPENSE_CATEGORIES : fallbackExpenseCategories)
      : (Array.isArray(window.JGC_INCOME_CATEGORIES) && window.JGC_INCOME_CATEGORIES.length ? window.JGC_INCOME_CATEGORIES : fallbackIncomeCategories);

    if (!els.entryCategory) return;

    els.entryCategory.innerHTML = ['<option value="">Select category</option>']
      .concat(categories.map((category) => `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`))
      .join("");
  }

  function setType(type) {
    state.activeType = type === "expense" ? "expense" : "income";
    els.entryType.value = state.activeType;

    document.querySelectorAll(".entry-tab").forEach((tab) => {
      tab.classList.toggle("active", tab.dataset.type === state.activeType);
    });

    if (els.partyLabel) {
      els.partyLabel.textContent = state.activeType === "income" ? "Received From" : "Paid To";
    }

    if (els.entryPartyName) {
      els.entryPartyName.placeholder = state.activeType === "income" ? "Enter name" : "Enter vendor / person";
    }

    populateCategories(state.activeType);
  }

  function setDefaultDates() {
    const today = new Date();
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
    const toISO = (date) => date.toISOString().slice(0, 10);

    if (els.entryDate) {
      els.entryDate.value = toISO(today);
    }

    if (els.filterFromDate && !els.filterFromDate.value) {
      els.filterFromDate.value = toISO(monthStart);
    }

    if (els.filterToDate && !els.filterToDate.value) {
      els.filterToDate.value = toISO(today);
    }
  }

  function getFilteredParams() {
    const params = new URLSearchParams();

    if (els.transactionSearch && els.transactionSearch.value.trim()) {
      params.set("search", els.transactionSearch.value.trim());
    }

    if (els.filterProject && els.filterProject.value && els.filterProject.value !== "all") {
      params.set("project_id", els.filterProject.value);
    }

    if (els.filterFromDate && els.filterFromDate.value) {
      params.set("from_date", els.filterFromDate.value);
    }

    if (els.filterToDate && els.filterToDate.value) {
      params.set("to_date", els.filterToDate.value);
    }

    return params;
  }

  function closeEntryMenu() {
    if (!els.entryActionMenu || !els.entryActionToggle) return;
    els.entryActionMenu.classList.remove("show");
    els.entryActionMenu.setAttribute("aria-hidden", "true");
    els.entryActionToggle.setAttribute("aria-expanded", "false");
  }

  function openEntryMenu() {
    if (!els.entryActionMenu || !els.entryActionToggle) return;
    els.entryActionMenu.classList.add("show");
    els.entryActionMenu.setAttribute("aria-hidden", "false");
    els.entryActionToggle.setAttribute("aria-expanded", "true");
  }

  function toggleEntryMenu() {
    if (!els.entryActionMenu) return;
    if (els.entryActionMenu.classList.contains("show")) {
      closeEntryMenu();
    } else {
      openEntryMenu();
    }
  }

  function openNewEntryForm() {
    resetForm();
    document.querySelector(".form-panel")?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function openImportPicker() {
    if (!els.excelImportInput) return;
    els.excelImportInput.value = "";
    els.excelImportInput.click();
  }

  function openReportDownload() {
    const params = getFilteredParams();
    const url = `download_income_report.php${params.toString() ? `?${params.toString()}` : ""}`;
    window.open(url, "_blank", "noopener");
  }

  async function importExcelFile(file) {
    if (!file) return;

    const extension = (file.name.split(".").pop() || "").toLowerCase();
    if (!["xlsx", "csv"].includes(extension)) {
      alert("Please upload a CSV or XLSX file.");
      return;
    }

    const formData = new FormData();
    formData.append("file", file);

    try {
      const response = await fetch("import_income_excel.php", {
        method: "POST",
        body: formData
      });
      const result = await response.json();

      if (!result.success) {
        const details = Array.isArray(result.errors) && result.errors.length
          ? `\n\n${result.errors.join("\n")}`
          : "";
        throw new Error((result.message || "Import failed") + details);
      }

      await loadTransactions();
      alert(result.message || "Excel file imported successfully.");
    } catch (error) {
      alert(error.message || "Unable to import Excel file.");
    }
  }

  async function loadTransactions() {
    const params = getFilteredParams();
    const url = `api/get_income_expense.php${params.toString() ? `?${params.toString()}` : ""}`;

    try {
      const response = await fetch(url, { headers: { Accept: "application/json" } });
      const payload = await response.json();
      state.rows = Array.isArray(payload.rows) ? payload.rows : [];
      state.filteredRows = [...state.rows];
      state.page = 1;
      renderSummary(payload.summary || {});
      renderTable();
      renderCharts(payload);
      renderCashSummary(payload.summary || {});
    } catch (error) {
      console.error(error);
      if (els.summaryCards) {
        els.summaryCards.innerHTML = "";
      }
      if (els.transactionTableBody) {
        els.transactionTableBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">Unable to load transactions.</td></tr>`;
      }
    }
  }

  function renderSummary(summary) {
    const cards = [
      {
        title: "Total Income",
        icon: "bi-arrow-up-right-circle-fill",
        amount: formatCurrency(summary.total_income || 0),
        trend: summary.income_trend,
        series: summary.series_income || [],
        tone: "positive"
      },
      {
        title: "Total Expense",
        icon: "bi-arrow-down-right-circle-fill",
        amount: formatCurrency(summary.total_expense || 0),
        trend: summary.expense_trend,
        series: summary.series_expense || [],
        tone: "negative"
      },
      {
        title: "Net Profit / Loss",
        icon: "bi-wallet2",
        amount: formatCurrency(summary.net_profit || 0),
        trend: summary.profit_trend,
        series: summary.series_profit || [],
        tone: Number(summary.net_profit || 0) >= 0 ? "positive" : "negative"
      },
      {
        title: "Total Transactions",
        icon: "bi-receipt-cutoff",
        amount: String(summary.total_transactions || 0),
        trend: summary.transaction_trend,
        series: summary.series_transactions || [],
        tone: "neutral"
      }
    ];

    if (!els.summaryCards) return;

    els.summaryCards.innerHTML = cards.map((card) => `
      <div class="metric-card">
        <div class="metric-top">
          <div>
            <div class="metric-title">${card.title}</div>
            <div class="metric-amount">${card.amount}</div>
          </div>
          <div class="metric-icon"><i class="bi ${card.icon}"></i></div>
        </div>
        <div class="metric-subrow">
          ${formatTrend(card.trend)}
          <div class="metric-sparkline ${card.tone}">${buildSparkline(card.series)}</div>
        </div>
      </div>
    `).join("");
  }

  function renderTable() {
    const rows = state.rows;
    const totalRows = rows.length;
    const totalPages = Math.max(1, Math.ceil(totalRows / state.limit));
    if (state.page > totalPages) {
      state.page = totalPages;
    }

    const start = (state.page - 1) * state.limit;
    const visibleRows = rows.slice(start, start + state.limit);

    if (!els.transactionTableBody) return;

    if (!visibleRows.length) {
      els.transactionTableBody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-4">No transactions found.</td></tr>`;
    } else {
      els.transactionTableBody.innerHTML = visibleRows.map((row) => `
        <tr>
          <td>#${row.id}</td>
          <td>${escapeHtml(formatDate(row.entry_date))}</td>
          <td><span class="row-type ${escapeHtml(row.type)}">${escapeHtml(row.type)}</span></td>
          <td>${escapeHtml(row.category || "-")}</td>
          <td>${escapeHtml(row.project_title || "-")}</td>
          <td>${escapeHtml(row.description || "-")}</td>
          <td>${escapeHtml(row.party_name || "-")}</td>
          <td class="amount-cell ${escapeHtml(row.type)}">${escapeHtml(formatCurrency(row.amount))}</td>
          <td>${escapeHtml(row.payment_method || "-")}</td>
          <td>
            <div class="row-actions">
              <button class="row-action-btn view" type="button" data-action="view" data-id="${escapeHtml(row.id)}" title="View"><i class="bi bi-eye"></i></button>
              <button class="row-action-btn edit" type="button" data-action="edit" data-id="${escapeHtml(row.id)}" title="Edit"><i class="bi bi-pencil-square"></i></button>
              <button class="row-action-btn delete" type="button" data-action="delete" data-id="${escapeHtml(row.id)}" title="Delete"><i class="bi bi-trash3"></i></button>
            </div>
          </td>
        </tr>
      `).join("");
    }

    if (els.tableMeta) {
      const from = totalRows ? start + 1 : 0;
      const to = Math.min(start + state.limit, totalRows);
      els.tableMeta.textContent = `Showing ${from} to ${to} of ${totalRows} entries`;
    }

    renderPagination(totalPages);
  }

  function renderPagination(totalPages) {
    if (!els.paginationWrap) return;

    if (totalPages <= 1) {
      els.paginationWrap.innerHTML = "";
      return;
    }

    const pageItems = [];
    pageItems.push(`<li class="page-item ${state.page === 1 ? "disabled" : ""}"><a class="page-link" href="#" data-page="${state.page - 1}">Prev</a></li>`);

    const visiblePages = [];
    for (let page = 1; page <= totalPages; page += 1) {
      if (page === 1 || page === totalPages || Math.abs(page - state.page) <= 1) {
        visiblePages.push(page);
      }
    }

    let lastPage = 0;
    visiblePages.forEach((page) => {
      if (page - lastPage > 1) {
        pageItems.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
      }
      pageItems.push(`<li class="page-item ${state.page === page ? "active" : ""}"><a class="page-link" href="#" data-page="${page}">${page}</a></li>`);
      lastPage = page;
    });

    pageItems.push(`<li class="page-item ${state.page === totalPages ? "disabled" : ""}"><a class="page-link" href="#" data-page="${state.page + 1}">Next</a></li>`);
    els.paginationWrap.innerHTML = `<ul class="pagination pagination-sm">${pageItems.join("")}</ul>`;
  }

  function aggregateByDate(rows) {
    const grouped = new Map();

    rows.forEach((row) => {
      const date = row.entry_date || "Unknown";
      if (!grouped.has(date)) {
        grouped.set(date, { income: 0, expense: 0, transactions: 0 });
      }
      const bucket = grouped.get(date);
      const amount = Number(row.amount || 0);
      bucket.transactions += 1;
      if (row.type === "expense") {
        bucket.expense += amount;
      } else {
        bucket.income += amount;
      }
    });

    const sortedDates = Array.from(grouped.keys()).sort();
    let running = 0;

    return sortedDates.map((date) => {
      const bucket = grouped.get(date);
      running += bucket.income - bucket.expense;
      return {
        date,
        income: bucket.income,
        expense: bucket.expense,
        transactions: bucket.transactions,
        profit: bucket.income - bucket.expense,
        cashFlow: running
      };
    });
  }

  function aggregateByCategory(rows, type) {
    const grouped = new Map();

    rows.filter((row) => row.type === type).forEach((row) => {
      const category = row.category || "Others";
      grouped.set(category, (grouped.get(category) || 0) + Number(row.amount || 0));
    });

    return Array.from(grouped.entries())
      .sort((a, b) => b[1] - a[1])
      .map(([label, value]) => ({ label, value }));
  }

  function renderCashSummary(summary) {
    if (!els.cashSummary) return;

    const opening = Number(summary.opening_balance || 0);
    const totalIncome = Number(summary.total_income || 0);
    const totalExpense = Number(summary.total_expense || 0);
    const closing = Number(summary.net_profit || 0);

    els.cashSummary.innerHTML = `
      <div class="cash-row"><span>Opening Balance</span><strong>${formatCurrency(opening)}</strong></div>
      <div class="cash-row"><span>Total Income</span><strong class="positive">${formatCurrency(totalIncome)}</strong></div>
      <div class="cash-row"><span>Total Expense</span><strong class="negative">${formatCurrency(totalExpense)}</strong></div>
      <div class="cash-row"><span>Closing Balance</span><strong class="${closing >= 0 ? "positive" : "negative"}">${formatCurrency(closing)}</strong></div>
    `;
  }

  function destroyChart(key) {
    if (state.charts[key]) {
      state.charts[key].destroy();
      state.charts[key] = null;
    }
  }

  function renderCharts(payload) {
    const rows = Array.isArray(payload.rows) ? payload.rows : [];
    const timeline = aggregateByDate(rows);
    const incomeCategory = aggregateByCategory(rows, "income");
    const expenseCategory = aggregateByCategory(rows, "expense");

    const totalIncome = timeline.reduce((sum, item) => sum + item.income, 0);
    const totalExpense = timeline.reduce((sum, item) => sum + item.expense, 0);

    destroyChart("incomeExpense");
    destroyChart("incomeCategory");
    destroyChart("expenseCategory");
    destroyChart("cashFlow");

    if (els.incomeExpenseChart) {
      state.charts.incomeExpense = new Chart(els.incomeExpenseChart, {
        type: "doughnut",
        data: {
          labels: ["Income", "Expense"],
          datasets: [{
            data: [totalIncome, totalExpense],
            backgroundColor: ["#2eab5d", "#e04d4d"],
            borderWidth: 0,
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "68%",
          plugins: {
            legend: { display: false }
          }
        }
      });
    }

    if (els.incomeCategoryChart) {
      state.charts.incomeCategory = new Chart(els.incomeCategoryChart, {
        type: "doughnut",
        data: {
          labels: incomeCategory.map((item) => item.label),
          datasets: [{
            data: incomeCategory.map((item) => item.value),
            backgroundColor: ["#2eab5d", "#d8b15c", "#9e7fd1", "#e58b64", "#76a9ea"],
            borderWidth: 0,
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "68%",
          plugins: {
            legend: {
              position: "right",
              labels: { usePointStyle: true, boxWidth: 8 }
            }
          }
        }
      });
    }

    if (els.expenseCategoryChart) {
      state.charts.expenseCategory = new Chart(els.expenseCategoryChart, {
        type: "doughnut",
        data: {
          labels: expenseCategory.map((item) => item.label),
          datasets: [{
            data: expenseCategory.map((item) => item.value),
            backgroundColor: ["#e04d4d", "#d8b15c", "#f0a34d", "#8c72cf", "#70a2e8", "#59c1b5"],
            borderWidth: 0,
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: "68%",
          plugins: {
            legend: {
              position: "right",
              labels: { usePointStyle: true, boxWidth: 8 }
            }
          }
        }
      });
    }

    if (els.cashFlowChart) {
      state.charts.cashFlow = new Chart(els.cashFlowChart, {
        type: "line",
        data: {
          labels: timeline.length ? buildChartLabels(timeline) : ["No data"],
          datasets: [{
            label: "Cash Flow",
            data: timeline.length ? timeline.map((item) => item.cashFlow) : [0],
            fill: true,
            borderColor: "#b68d40",
            backgroundColor: "rgba(182, 141, 64, 0.14)",
            tension: 0.35,
            pointRadius: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            x: { grid: { display: false } },
            y: { grid: { color: "rgba(0,0,0,0.06)" } }
          }
        }
      });
    }
  }

  function findRow(id) {
    return state.rows.find((row) => String(row.id) === String(id));
  }

  function showViewModal(row) {
    if (!els.viewModal || !row) return;

    els.viewModalTitle.textContent = `${row.type === "expense" ? "Expense" : "Income"} #${row.id}`;
    els.viewModalBody.innerHTML = `
      <div class="detail-row"><div class="detail-label">Project</div><div class="detail-value">${escapeHtml(row.project_title || "-")}</div></div>
      <div class="detail-row"><div class="detail-label">Date</div><div class="detail-value">${escapeHtml(formatDate(row.entry_date))}</div></div>
      <div class="detail-row"><div class="detail-label">Type</div><div class="detail-value">${escapeHtml(row.type)}</div></div>
      <div class="detail-row"><div class="detail-label">Category</div><div class="detail-value">${escapeHtml(row.category || "-")}</div></div>
      <div class="detail-row"><div class="detail-label">Party</div><div class="detail-value">${escapeHtml(row.party_name || "-")}</div></div>
      <div class="detail-row"><div class="detail-label">Amount</div><div class="detail-value">${escapeHtml(formatCurrency(row.amount))}</div></div>
      <div class="detail-row"><div class="detail-label">Payment Method</div><div class="detail-value">${escapeHtml(row.payment_method || "-")}</div></div>
      <div class="detail-row"><div class="detail-label">Description</div><div class="detail-value">${escapeHtml(row.description || "-")}</div></div>
      <div class="detail-row"><div class="detail-label">Receipt</div><div class="detail-value">${row.receipt ? `<a href="${escapeHtml(row.receipt)}" target="_blank" rel="noopener">View receipt</a>` : "No receipt uploaded"}</div></div>
    `;
    els.viewModal.classList.add("active");
    els.viewModal.setAttribute("aria-hidden", "false");
  }

  function closeViewModal() {
    if (!els.viewModal) return;
    els.viewModal.classList.remove("active");
    els.viewModal.setAttribute("aria-hidden", "true");
  }

  function fillForm(row) {
    if (!row) return;

    state.editingId = row.id;
    els.entryId.value = row.id;
    setType(row.type);
    els.entryProject.value = row.project_id || "";
    els.entryCategory.value = row.category || "";
    els.entryDate.value = row.entry_date || "";
    els.entryAmount.value = row.amount || "";
    els.entryPaymentMethod.value = row.payment_method || "";
    els.entryPartyName.value = row.party_name || "";
    els.entryDescription.value = row.description || "";
    els.saveEntryBtn.textContent = "Update Entry";
    els.entryForm.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function resetForm() {
    state.editingId = null;
    els.entryId.value = "";
    els.entryForm.reset();
    setType("income");
    setDefaultDates();
    els.saveEntryBtn.textContent = "Save Entry";
  }

  async function deleteRow(id) {
    const row = findRow(id);
    if (!row) return;

    const confirmed = window.confirm(`Delete ${row.type} entry #${row.id}?`);
    if (!confirmed) return;

    const formData = new FormData();
    formData.append("id", id);

    try {
      const response = await fetch("api/delete_income_expense.php", {
        method: "POST",
        body: formData
      });
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || "Delete failed");
      }
      await loadTransactions();
    } catch (error) {
      alert(error.message || "Unable to delete entry.");
    }
  }

  async function handleSubmit(event) {
    event.preventDefault();

    const formData = new FormData(els.entryForm);
    formData.set("type", state.activeType);

    if (!formData.get("project_id")) {
      alert("Please select a project.");
      return;
    }

    if (!formData.get("category")) {
      alert("Please select a category.");
      return;
    }

    if (!formData.get("amount")) {
      alert("Please enter an amount.");
      return;
    }

    try {
      els.saveEntryBtn.disabled = true;
      const response = await fetch("api/add_income_expense.php", {
        method: "POST",
        body: formData
      });
      const result = await response.json();
      if (!result.success) {
        throw new Error(result.message || "Save failed");
      }

      resetForm();
      await loadTransactions();
    } catch (error) {
      alert(error.message || "Unable to save entry.");
    } finally {
      els.saveEntryBtn.disabled = false;
    }
  }

  function bindEvents() {
    document.querySelectorAll(".entry-tab").forEach((tab) => {
      tab.addEventListener("click", () => setType(tab.dataset.type));
    });

    if (els.addEntryBtn) {
      els.addEntryBtn.addEventListener("click", openNewEntryForm);
    }

    if (els.entryActionToggle) {
      els.entryActionToggle.addEventListener("click", (event) => {
        event.stopPropagation();
        toggleEntryMenu();
      });
    }

    if (els.entryActionMenu) {
      els.entryActionMenu.addEventListener("click", (event) => {
        const item = event.target.closest("[data-menu-action]");
        if (!item) return;

        const action = item.dataset.menuAction;
        closeEntryMenu();

        if (action === "add") {
          openNewEntryForm();
        } else if (action === "upload") {
          openImportPicker();
        } else if (action === "report") {
          openReportDownload();
        }
      });
    }

    if (els.entryForm) {
      els.entryForm.addEventListener("submit", handleSubmit);
    }

    if (els.excelImportInput) {
      els.excelImportInput.addEventListener("change", () => {
        const [file] = els.excelImportInput.files || [];
        if (file) {
          importExcelFile(file);
        }
      });
    }

    if (els.resetEntryBtn) {
      els.resetEntryBtn.addEventListener("click", (event) => {
        event.preventDefault();
        resetForm();
      });
    }

    if (els.transactionSearch) {
      let searchTimer = null;
      els.transactionSearch.addEventListener("input", () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(loadTransactions, 250);
      });
    }

    [els.filterFromDate, els.filterToDate, els.filterProject].forEach((input) => {
      if (!input) return;
      input.addEventListener("change", loadTransactions);
    });

    if (els.clearFiltersBtn) {
      els.clearFiltersBtn.addEventListener("click", () => {
        if (els.transactionSearch) els.transactionSearch.value = "";
        if (els.filterProject) els.filterProject.value = "all";
        setDefaultDates();
        loadTransactions();
      });
    }

    if (els.closeViewModalBtn) {
      els.closeViewModalBtn.addEventListener("click", closeViewModal);
    }

    if (els.viewModal) {
      els.viewModal.addEventListener("click", (event) => {
        if (event.target === els.viewModal) {
          closeViewModal();
        }
      });
    }

    if (els.paginationWrap) {
      els.paginationWrap.addEventListener("click", (event) => {
        const link = event.target.closest("[data-page]");
        if (!link) return;
        event.preventDefault();
        const page = Number(link.dataset.page);
        if (Number.isNaN(page) || page < 1) return;
        const totalPages = Math.max(1, Math.ceil(state.rows.length / state.limit));
        state.page = Math.min(page, totalPages);
        renderTable();
      });
    }

    document.addEventListener("click", (event) => {
      if (els.entryActionMenu && els.entryActionToggle) {
        const clickedInside = event.target.closest("#entryActionGroup");
        if (!clickedInside) {
          closeEntryMenu();
        }
      }

      const actionButton = event.target.closest("[data-action]");
      if (!actionButton) return;

      const { action, id } = actionButton.dataset;
      const row = findRow(id);
      if (!row) return;

      if (action === "view") {
        showViewModal(row);
      } else if (action === "edit") {
        fillForm(row);
      } else if (action === "delete") {
        deleteRow(id);
      }
    });
  }

  function init() {
    populateProjects();
    setDefaultDates();
    setType("income");
    bindEvents();
    loadTransactions();
  }

  document.addEventListener("DOMContentLoaded", init);

})();
