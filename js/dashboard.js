// ============================
// ?? DATE ONLY
// ============================
function updateDate() {
  const dateElement = document.getElementById("currentDate");
  if (!dateElement) return;

  const today = new Date();

  const options = {
    year: "numeric",
    month: "long",
    day: "numeric"
  };

  dateElement.innerText = today.toLocaleDateString("en-US", options);
}

// ============================
// ?? LIVE DATE + TIME
// ============================
function updateDateTime() {
  const dateElement = document.getElementById("currentDate");
  if (!dateElement) return;

  const now = new Date();

  const date = now.toLocaleDateString("en-US", {
    year: "numeric",
    month: "long",
    day: "numeric"
  });

  const time = now.toLocaleTimeString("en-US");

  dateElement.innerText = date + " | " + time;
}

const STACK_INTERVAL_MS = 4500;
const STACK_EASING = "cubic-bezier(0.22, 1, 0.36, 1)";
const syncedStacks = new Set();
let syncedIntervalId = null;
const rotatingStacks = new WeakSet();
let dashboardData = [];
let dashboardSummary = {};
let dashboardMaterials = [];
let dashboardAlerts = [];
let dashboardUpdates = [];

function getDashboardTotals() {
  const summary = dashboardSummary || {};
  const totals = {
    projects: Number(summary.project_count || dashboardData.length || 0),
    workers: Number(summary.worker_count || 0),
    materials: Number(summary.material_quantity || 0),
    expense: Number(summary.spent_total || summary.total_cost || 0),
    activeWorkers: Number(summary.present_today || 0),
    absentWorkers: Number(summary.absent_today || 0)
  };
  return totals;
}

function getProjectStatusLabel(progress) {
  const value = Number(progress || 0);
  if (value >= 80) return "On Track";
  if (value >= 50) return "Watch";
  return "Delayed";
}

function renderAlertsSection(projects, alerts) {
  const container = document.getElementById("alertsList");
  if (!container) return;

  const renderedAlerts = Array.isArray(alerts) && alerts.length ? alerts : [{
    title: projects.length ? "Pending updates need review" : "No project data available",
    text: projects.length ? "Review the latest updates, stock warnings, and project activity." : "Add projects, workers, and materials to populate the dashboard.",
    meta: projects.length ? "Review" : "Empty state"
  }];

  container.innerHTML = renderedAlerts.slice(0, 4).map((alert) => `
    <div class="alert-item">
      <div class="alert-item-title">
        <span class="alert-dot"></span>
        <div>
          <h4>${alert.title}</h4>
          <p>${alert.text}</p>
        </div>
      </div>
      <div class="alert-meta">${alert.meta}</div>
    </div>
  `).join("");
}

function renderProjectStatusSection(projects) {
  const container = document.getElementById("projectStatusList");
  if (!container) return;

  const topProjects = [...projects]
    .sort((a, b) => Number(b.progress || 0) - Number(a.progress || 0))
    .slice(0, 3);

  container.innerHTML = topProjects.map((project) => {
    const progress = Number(project.progress || 0);
    return `
      <div class="status-item">
        <div class="status-topline">
          <div class="status-name">
            <h4>${getProjectTitle(project)}</h4>
            <p>${project.city || "Unknown location"}</p>
          </div>
          <div class="status-percent">${progress}%</div>
        </div>
        <div class="status-topline" style="margin-bottom:0;">
          <div class="status-badge ${progress >= 80 ? "available" : progress >= 50 ? "ordered" : "low"}">${getProjectStatusLabel(progress)}</div>
          <div class="activity-meta">${Number(project.active_workers || 0)} workers</div>
        </div>
        <div class="progress-track"><div class="progress-fill-gold" style="width:${progress}%"></div></div>
      </div>
    `;
  }).join("");
}

function renderLabourSummarySection(summary) {
  const container = document.getElementById("labourSummaryBox");
  if (!container) return;

  const totals = getDashboardTotals();
  container.innerHTML = `
    <div class="summary-stat">
      <div class="summary-stat-row">
        <div>
          <div class="summary-stat-label">Total Workers</div>
          <div class="summary-stat-value">${totals.workers}</div>
        </div>
        <div class="overview-icon" style="width:48px;height:48px;font-size:20px;"><i class="bi bi-people"></i></div>
      </div>
      <div class="summary-stat-sub">Across all active projects</div>
    </div>
    <div class="summary-stat">
      <div class="summary-stat-row">
        <div>
          <div class="summary-stat-label">Present Today</div>
          <div class="summary-stat-value">${totals.activeWorkers}</div>
        </div>
        <div class="status-badge available">On site</div>
      </div>
    </div>
    <div class="summary-stat">
      <div class="summary-stat-row">
        <div>
          <div class="summary-stat-label">Absent Today</div>
          <div class="summary-stat-value">${totals.absentWorkers}</div>
        </div>
        <div class="status-badge low">Review</div>
      </div>
    </div>
  `;
}

function renderMaterialStatusSection(materials) {
  const container = document.getElementById("materialStatusBox");
  if (!container) return;

  if (!Array.isArray(materials) || !materials.length) {
    container.innerHTML = `
      <div class="material-item">
        <div class="material-row">
          <div>
            <div class="material-label">No low stock items</div>
            <div class="summary-stat-value">0</div>
          </div>
          <span class="inventory-badge available">OK</span>
        </div>
        <div class="material-sub">All tracked stock is above minimum level.</div>
      </div>
    `;
    return;
  }

  container.innerHTML = materials.slice(0, 3).map((material) => {
    const available = Number(material.available_qty || 0);
    const minimum = Number(material.min_qty || 0);
    const badgeClass = available <= minimum ? "low" : "available";
    const badgeText = available <= minimum ? "Low" : "OK";

    return `
      <div class="material-item">
        <div class="material-row">
          <div>
            <div class="material-label">${material.material_name || "Material"}</div>
            <div class="summary-stat-value">${available.toLocaleString("en-US")}</div>
          </div>
          <span class="inventory-badge ${badgeClass}">${badgeText}</span>
        </div>
        <div class="material-sub">${material.site_name || "Site"} · Min ${minimum.toLocaleString("en-US")}</div>
      </div>
    `;
  }).join("");
}

function renderRecentActivitySection(projects, updates) {
  const container = document.getElementById("recentActivityList");
  if (!container) return;

  const renderFallback = () => {
    const latestProjects = [...projects].sort((a, b) => Number(b.progress || 0) - Number(a.progress || 0)).slice(0, 3);
    if (!latestProjects.length) {
      container.innerHTML = `
        <div class="activity-item">
          <div class="activity-copy">
            <span class="activity-bullet"></span>
            <div>
              <h4>No recent activity yet</h4>
              <p>Add projects, workers, materials, and reports to populate the timeline.</p>
            </div>
          </div>
          <div class="activity-meta">Now</div>
        </div>
      `;
      return;
    }

    container.innerHTML = latestProjects.map((project, index) => {
      const label = index === 0 ? "Latest progress" : index === 1 ? "Worker update" : "Material update";
      const detail = index === 0
        ? `${Number(project.progress || 0)}% complete in ${project.city || "the project"}`
        : index === 1
          ? `${Number(project.active_workers || 0)} active workers today`
          : `${Number(project.materials_total || 0).toLocaleString("en-US")} material units logged`;

      return `
        <div class="activity-item">
          <div class="activity-copy">
            <span class="activity-bullet"></span>
            <div>
              <h4>${label}: ${getProjectTitle(project)}</h4>
              <p>${detail}</p>
            </div>
          </div>
          <div class="activity-meta">${index === 0 ? "Live" : index === 1 ? "Today" : "Updated"}</div>
        </div>
      `;
    }).join("");
  };

  if (Array.isArray(updates) && updates.length) {
    container.innerHTML = updates.slice(0, 4).map((update) => `
      <div class="activity-item ${update.category === "PROGRESS" ? "active" : ""}">
        <div>
          <h6>${update.title || "Update"}</h6>
          <p>${update.description || "Activity recorded"}</p>
        </div>
        <span>${update.created_at || "Just now"}</span>
      </div>
    `).join("");
    return;
  }

  renderFallback();
}

function renderDashboardPanels(projects, materials, alerts, updates, summary) {
  renderAlertsSection(projects, alerts);
  renderProjectStatusSection(projects);
  renderLabourSummarySection(summary);
  renderMaterialStatusSection(materials);
  renderRecentActivitySection(projects, updates);
}

function applyStackLayout(stackElement) {
  const cards = Array.from(stackElement.children);

  cards.forEach((card, index) => {
    const offset = index * 14;
    const scale = 1 - index * 0.06;
    const opacity = Math.max(1 - index * 0.25, 0.2);

    card.style.transform = `translateY(${offset}px) scale(${scale})`;
    card.style.opacity = String(opacity);
    card.style.zIndex = String(100 - index);
    card.style.transition = "all 0.6s cubic-bezier(0.22, 1, 0.36, 1)";
    card.style.filter = index === 0 ? "blur(0px)" : `blur(${Math.min(index * 0.35, 1)}px)`;

    if (index === 0) {
      card.style.boxShadow = "0 20px 40px rgba(0,0,0,0.15)";
    } else {
      card.style.boxShadow = "0 8px 20px rgba(0,0,0,0.08)";
    }
  });
}

function rotateStack(stackElement) {
  if (!stackElement || stackElement.children.length < 2) return;
  if (rotatingStacks.has(stackElement)) return;

  rotatingStacks.add(stackElement);

  const cards = Array.from(stackElement.children);
  const firstCard = cards[0];

  // Step 1: Animate front card moving down smoothly.
  firstCard.style.transform = "translateY(80px) scale(0.8)";
  firstCard.style.opacity = "0";
  firstCard.style.filter = "blur(2px)";

  // Move remaining cards forward now so there is no visual gap.
  cards.slice(1).forEach((card, index) => {
    const offset = index * 14;
    const scale = 1 - index * 0.06;
    const opacity = Math.max(1 - index * 0.25, 0.2);

    card.style.transform = `translateY(${offset}px) scale(${scale})`;
    card.style.opacity = String(opacity);
    card.style.zIndex = String(100 - index);
    card.style.filter = index === 0 ? "blur(0px)" : `blur(${Math.min(index * 0.35, 1)}px)`;
    card.style.boxShadow = index === 0
      ? "0 20px 40px rgba(0,0,0,0.15)"
      : "0 8px 20px rgba(0,0,0,0.08)";
  });

  // Step 2: Delay re-ordering for a natural flow.
  setTimeout(() => {
    firstCard.style.transition = "none";
    const backIndex = cards.length - 1;
    const backOffset = backIndex * 14;
    const backScale = 1 - backIndex * 0.06;
    const backOpacity = Math.max(1 - backIndex * 0.25, 0.2);

    firstCard.style.transform = `translateY(${backOffset}px) scale(${backScale})`;
    firstCard.style.opacity = String(backOpacity);
    firstCard.style.zIndex = String(100 - backIndex);
    firstCard.style.filter = `blur(${Math.min(backIndex * 0.35, 1)}px)`;
    firstCard.style.boxShadow = "0 8px 20px rgba(0,0,0,0.08)";

    stackElement.appendChild(firstCard);

    applyStackLayout(stackElement);

    firstCard.style.transition = `all 0.6s ${STACK_EASING}`;
    rotatingStacks.delete(stackElement);
  }, 600);
}

function stopStackAutoRotation(stackElement) {
  if (stackElement) {
    syncedStacks.delete(stackElement);
  }

  if (syncedStacks.size === 0 && syncedIntervalId) {
    clearInterval(syncedIntervalId);
    syncedIntervalId = null;
  }
}

function startStackAutoRotation(stackElement) {
  if (!stackElement) return;

  syncedStacks.add(stackElement);

  if (!syncedIntervalId) {
    syncedIntervalId = setInterval(() => {
      syncedStacks.forEach((stack) => {
        rotateStack(stack);
      });
    }, STACK_INTERVAL_MS);
  }
}

function formatCurrency(value) {
  return Number(value || 0).toLocaleString("en-US");
}

function getProjectTitle(project) {
  return project.title || project.name || "Untitled Project";
}

function createWorkersCard(project) {
  const card = document.createElement("article");
  card.className = "stack-item";
  card.innerHTML = `
    <p class="stack-project">${getProjectTitle(project)}</p>
    <p class="stack-sub">${project.city || "Unknown location"}</p>
    <h3 class="stack-main-value">${project.active_workers}</h3>
    <p class="stack-sub">of ${project.workers_total} workers active today</p>
  `;
  return card;
}

function createProgressCard(project) {
  const card = document.createElement("article");
  card.className = "stack-item";
  card.innerHTML = `
    <p class="stack-project">${getProjectTitle(project)}</p>
    <p class="stack-sub">${project.city || "Unknown location"}</p>
    <h3 class="stack-main-value">${project.progress}%</h3>
    <p class="stack-sub">project completion status</p>
    <div class="stack-progress"><span style="width:${project.progress}%"></span></div>
  `;
  return card;
}

function createExpenseCard(project) {
  const card = document.createElement("article");
  card.className = "stack-item";
  card.innerHTML = `
    <p class="stack-project">${getProjectTitle(project)}</p>
    <p class="stack-sub">${project.city || "Unknown location"}</p>
    <h3 class="stack-main-value">₹${formatCurrency(project.expense)}</h3>
    <p class="stack-sub">expense from reports</p>
  `;
  return card;
}

function createMaterialsCard(project) {
  const card = document.createElement("article");
  card.className = "stack-item";
  card.innerHTML = `
    <p class="stack-project">${getProjectTitle(project)}</p>
    <p class="stack-sub">${project.city || "Unknown location"}</p>
    <h3 class="stack-main-value">${Number(project.materials_total).toLocaleString("en-US")}</h3>
    <p class="stack-sub">material quantity logged</p>
  `;
  return card;
}

function createEmptyCard(message) {
  const card = document.createElement("article");
  card.className = "stack-item";
  card.innerHTML = `
    <p class="stack-project">No projects</p>
    <h3 class="stack-main-value">0</h3>
    <p class="stack-sub">${message}</p>
  `;
  return card;
}

function renderStackedCards(projects) {
  const workersStack = document.getElementById("workersCardStack");
  const progressStack = document.getElementById("progressCardStack");
  const expenseStack = document.getElementById("expenseCardStack");
  const materialsStack = document.getElementById("materialsCardStack");

  if (!workersStack) {
    console.warn("Missing #workersCardStack element");
  }

  if (!progressStack) {
    console.warn("Missing #progressCardStack element");
  }

  if (!expenseStack) {
    console.warn("Missing #expenseCardStack element");
  }

  if (!materialsStack) {
    console.warn("Missing #materialsCardStack element");
  }

  if (workersStack) {
    if (!workersStack.dataset.rendered) {
      workersStack.innerHTML = "";
      (projects.length ? projects : [null]).forEach(project => {
        workersStack.appendChild(project ? createWorkersCard(project) : createEmptyCard("No worker data available"));
      });
      workersStack.dataset.rendered = "true";
    }

    applyStackLayout(workersStack);
    startStackAutoRotation(workersStack);
  }

  if (progressStack) {
    if (!progressStack.dataset.rendered) {
      progressStack.innerHTML = "";
      (projects.length ? projects : [null]).forEach(project => {
        progressStack.appendChild(project ? createProgressCard(project) : createEmptyCard("No progress data available"));
      });
      progressStack.dataset.rendered = "true";
    }

    applyStackLayout(progressStack);
    startStackAutoRotation(progressStack);
  }

  if (expenseStack) {
    if (!expenseStack.dataset.rendered) {
      expenseStack.innerHTML = "";
      (projects.length ? projects : [null]).forEach(project => {
        expenseStack.appendChild(project ? createExpenseCard(project) : createEmptyCard("No expense data available"));
      });
      expenseStack.dataset.rendered = "true";
    }

    applyStackLayout(expenseStack);
    startStackAutoRotation(expenseStack);
  }

  if (materialsStack) {
    if (!materialsStack.dataset.rendered) {
      materialsStack.innerHTML = "";
      (projects.length ? projects : [null]).forEach(project => {
        materialsStack.appendChild(project ? createMaterialsCard(project) : createEmptyCard("No materials data available"));
      });
      materialsStack.dataset.rendered = "true";
    }

    applyStackLayout(materialsStack);
    startStackAutoRotation(materialsStack);
  }
}

function updateSummarySection(summary) {
  dashboardSummary = summary || {};
  const phaseElement = document.getElementById("projectSummaryPhase");
  const titleElement = document.getElementById("projectSummaryTitle");
  const descriptionElement = document.getElementById("projectSummaryDescription");
  const progressElement = document.getElementById("projectSummaryProgress");
  const targetElement = document.getElementById("projectSummaryTarget");
  const fillElement = document.getElementById("projectSummaryFill");
  const overviewProjectsValue = document.getElementById("overviewProjectsValue");
  const overviewWorkersValue = document.getElementById("overviewWorkersValue");
  const overviewMaterialsValue = document.getElementById("overviewMaterialsValue");
  const overviewExpenseValue = document.getElementById("overviewExpenseValue");
  const budgetTotalValue = document.getElementById("budgetTotalValue");
  const budgetSpentValue = document.getElementById("budgetSpentValue");
  const budgetRemainingValue = document.getElementById("budgetRemainingValue");
  const budgetUtilizationValue = document.getElementById("budgetUtilizationValue");
  const budgetUtilizationFill = document.getElementById("budgetUtilizationFill");
  const lastUpdated = document.getElementById("lastUpdated");
  const syncStatus = document.getElementById("syncStatus");

  if (phaseElement) phaseElement.innerText = "PROJECT SUMMARY";
  if (titleElement) titleElement.innerText = summary.project_count > 0 ? "Overall Project Completion" : "No Projects Yet";
  if (descriptionElement) {
    descriptionElement.innerText = summary.project_count > 0
      ? `Across ${summary.project_count} project${summary.project_count === 1 ? "" : "s"}, ${summary.worker_count} workers and ${Number(summary.material_quantity || 0).toLocaleString("en-US")} material units are tracked.`
      : "Add a project to begin tracking workers, materials, and reports.";
  }
  if (progressElement) progressElement.innerText = `${summary.average_progress || 0}%`;
  if (targetElement) {
    targetElement.innerText = summary.project_count > 0
      ? `₹${formatCurrency(summary.spent_total || summary.total_cost || 0)} total cost`
      : "0 projects tracked";
  }
  if (fillElement) {
    fillElement.style.width = `${Math.max(0, Math.min(100, Number(summary.average_progress || 0)))}%`;
  }

  if (overviewProjectsValue) {
    overviewProjectsValue.innerText = String(summary.project_count || 0);
  }

  if (overviewWorkersValue) {
    overviewWorkersValue.innerText = String(summary.active_sites || 0);
  }

  if (overviewMaterialsValue) {
    overviewMaterialsValue.innerText = `₹${formatCurrency(summary.total_cost || 0)}`;
  }

  if (overviewExpenseValue) {
    overviewExpenseValue.innerText = `₹${formatCurrency(summary.pending_amount || 0)}`;
  }

  if (budgetTotalValue) {
    budgetTotalValue.innerText = `₹${formatCurrency(summary.budget_total || 0)}`;
  }

  if (budgetSpentValue) {
    budgetSpentValue.innerText = `₹${formatCurrency(summary.spent_total || 0)}`;
  }

  if (budgetRemainingValue) {
    budgetRemainingValue.innerText = `₹${formatCurrency(summary.remaining_budget || 0)}`;
  }

  if (budgetUtilizationValue) {
    budgetUtilizationValue.innerText = `${Number(summary.utilization_pct || 0)}%`;
  }

  if (budgetUtilizationFill) {
    budgetUtilizationFill.style.width = `${Math.max(0, Math.min(100, Number(summary.utilization_pct || 0)))}%`;
  }

  if (lastUpdated) {
    lastUpdated.innerText = `Last Updated: ${summary.last_updated || new Date().toLocaleString("en-IN")}`;
  }

  if (syncStatus) {
    syncStatus.innerText = summary.project_count > 0 ? "Live data synced from MySQL" : "No project data available";
  }
}

function renderRecentActivity(projects) {
  const container = document.getElementById("recentActivityList");
  if (!container) return;

  container.innerHTML = "";

  if (!projects.length) {
    container.innerHTML = `
      <div class="activity-item active">
        <div>
          <h6>No project activity yet</h6>
          <p>Add your first project, then workers, materials, and reports will appear here.</p>
        </div>
        <span>Now</span>
      </div>
    `;
    return;
  }

  const latestProjects = projects.slice(0, 3);
  const activityRows = latestProjects.map((project, index) => {
    const label = index === 0 ? "Project progress" : index === 1 ? "Worker activity" : "Material usage";
    const detail = index === 0
      ? `${project.progress}% complete in ${project.city || "the project"}`
      : index === 1
        ? `${project.active_workers} active workers of ${project.workers_total}`
        : `${Number(project.materials_total).toLocaleString("en-US")} material units logged`;

    return `
      <div class="activity-item ${index === 0 ? "active" : ""}">
        <div>
          <h6>${label}: ${getProjectTitle(project)}</h6>
          <p>${detail}</p>
        </div>
        <span>${index === 0 ? "Live" : index === 1 ? "Today" : "Updated"}</span>
      </div>
    `;
  });

  container.innerHTML = activityRows.join("");
}

function loadDashboard() {
  fetch("get_dashboard.php")
    .then((response) => response.json())
    .then((data) => {
      dashboardData = data.projects || [];
      dashboardSummary = data.summary || {};
      dashboardMaterials = data.materials || [];
      dashboardAlerts = data.alerts || [];
      dashboardUpdates = data.updates || [];
      updateSummarySection(dashboardSummary);
      renderStackedCards(dashboardData);
      renderDashboardPanels(dashboardData, dashboardMaterials, dashboardAlerts, dashboardUpdates, dashboardSummary);
    })
    .catch(() => {
      dashboardData = [];
      dashboardSummary = { project_count: 0, active_sites: 0, total_cost: 0, pending_amount: 0, budget_total: 0, spent_total: 0, remaining_budget: 0, utilization_pct: 0, average_progress: 0, worker_count: 0, present_today: 0, absent_today: 0, material_quantity: 0, last_updated: null };
      dashboardMaterials = [];
      dashboardAlerts = [];
      dashboardUpdates = [];
      updateSummarySection(dashboardSummary);
      renderStackedCards([]);
      renderDashboardPanels([], [], [], [], dashboardSummary);
    });
}

// ============================
// ?? INIT ON LOAD
// ============================
document.addEventListener("DOMContentLoaded", function () {
  // ?? Choose ONE:
  // updateDate();          // Static date
  updateDateTime();        // Live date + time

  // Update every second (only for live mode)
  setInterval(updateDateTime, 1000);

  loadDashboard();
});

