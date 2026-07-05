function formatCurrency(value) {
  return `₹ ${Number(value || 0).toLocaleString("en-IN")}`;
}

function formatDate(value) {
  if (!value) return "-";

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "-";

  return date.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric"
  });
}

function escapeHtml(value) {
  return String(value || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function addDays(dateValue, days) {
  const date = new Date(dateValue);
  if (Number.isNaN(date.getTime())) return null;
  date.setDate(date.getDate() + days);
  return date;
}

function formatDateFromDate(date) {
  if (!(date instanceof Date) || Number.isNaN(date.getTime())) return "-";

  return date.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric"
  });
}

function getProgressTier(progress) {
  if (progress >= 90) return "Almost done";
  if (progress >= 70) return "Finishing";
  if (progress >= 45) return "In progress";
  if (progress > 0) return "Starting";
  return "Pending";
}

function getProjectIdFromQuery() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id") || "";
}

function scrollToEditSection() {
  const editSection = document.getElementById("editSection");
  if (editSection) {
    editSection.scrollIntoView({ behavior: "smooth", block: "start" });
  }
}

function setText(id, value) {
  const element = document.getElementById(id);
  if (element) {
    element.textContent = value;
  }
}

/* 🔹 NEW REDESIGNED RENDERERS */

function renderWBS(project) {
  const container = document.getElementById("wbsTreeContainer");
  if (!container) return;

  const progress = Number(project.progress || 0);
  
  // Phase calculations
  const fProgress = Math.min(100, Math.max(0, Math.round((progress / 15) * 100)));
  const sProgress = Math.min(100, Math.max(0, Math.round(((progress - 15) / 35) * 100)));
  const fiProgress = Math.min(100, Math.max(0, Math.round(((progress - 50) / 50) * 100)));
  
  const fStatus = fProgress >= 100 ? "Completed" : fProgress > 0 ? "In Progress" : "Pending";
  const sStatus = sProgress >= 100 ? "Completed" : sProgress > 0 ? "In Progress" : "Pending";
  const fiStatus = fiProgress >= 100 ? "Completed" : fiProgress > 0 ? "In Progress" : "Pending";
  
  const fClass = fProgress >= 100 ? "completed" : fProgress > 0 ? "ongoing" : "pending";
  const sClass = sProgress >= 100 ? "completed" : sProgress > 0 ? "ongoing" : "pending";
  const fiClass = fiProgress >= 100 ? "completed" : fiProgress > 0 ? "ongoing" : "pending";

  container.innerHTML = `
    <!-- Phase 1: Foundation -->
    <div class="wbs-phase active">
      <div class="wbs-phase-header" onclick="toggleWBSNode(this)">
        <span class="wbs-phase-arrow"><i class="bi bi-chevron-down"></i></span>
        <strong class="wbs-phase-title"><i class="bi bi-folder-fill text-gold"></i> 1. Foundation</strong>
        <div class="wbs-phase-meta">
          <span class="wbs-badge ${fClass}">${fStatus}</span>
          <span class="wbs-count">3 Tasks</span>
          <span class="wbs-pct">${fProgress}%</span>
        </div>
      </div>
      <div class="wbs-phase-children">
        <div class="wbs-task">
          <span class="wbs-bullet ${fProgress >= 100 ? "completed" : "ongoing"}"></span>
          <span>1.1 Excavation</span>
          <span class="wbs-badge ${fProgress >= 100 ? "completed" : "ongoing"}">${fProgress >= 100 ? "Completed" : "In Progress"}</span>
        </div>
        <div class="wbs-task">
          <span class="wbs-bullet ${fProgress >= 100 ? "completed" : "pending"}"></span>
          <span>1.2 PCC</span>
          <span class="wbs-badge ${fProgress >= 100 ? "completed" : "pending"}">${fProgress >= 100 ? "Completed" : "Pending"}</span>
        </div>
        <div class="wbs-task">
          <span class="wbs-bullet ${fProgress >= 100 ? "completed" : "pending"}"></span>
          <span>1.3 Footing</span>
          <span class="wbs-badge ${fProgress >= 100 ? "completed" : "pending"}">${fProgress >= 100 ? "Completed" : "Pending"}</span>
        </div>
      </div>
    </div>

    <!-- Phase 2: Structure -->
    <div class="wbs-phase active">
      <div class="wbs-phase-header" onclick="toggleWBSNode(this)">
        <span class="wbs-phase-arrow"><i class="bi bi-chevron-down"></i></span>
        <strong class="wbs-phase-title"><i class="bi bi-folder-fill text-gold"></i> 2. Structure</strong>
        <div class="wbs-phase-meta">
          <span class="wbs-badge ${sClass}">${sStatus}</span>
          <span class="wbs-count">4 Tasks</span>
          <span class="wbs-pct">${sProgress}%</span>
        </div>
      </div>
      <div class="wbs-phase-children">
        <div class="wbs-task">
          <span class="wbs-bullet ${sProgress >= 25 ? "completed" : sProgress > 0 ? "ongoing" : "pending"}"></span>
          <span>2.1 Columns</span>
          <span class="wbs-badge ${sProgress >= 25 ? "completed" : sProgress > 0 ? "ongoing" : "pending"}">${sProgress >= 25 ? "Completed" : sProgress > 0 ? "In Progress" : "Pending"}</span>
        </div>
        <div class="wbs-task">
          <span class="wbs-bullet ${sProgress >= 50 ? "completed" : sProgress >= 25 ? "ongoing" : "pending"}"></span>
          <span>2.2 Beams</span>
          <span class="wbs-badge ${sProgress >= 50 ? "completed" : sProgress >= 25 ? "ongoing" : "pending"}">${sProgress >= 50 ? "Completed" : sProgress >= 25 ? "In Progress" : "Pending"}</span>
        </div>
        <div class="wbs-task">
          <span class="wbs-bullet ${sProgress >= 75 ? "completed" : sProgress >= 50 ? "ongoing" : "pending"}"></span>
          <span>2.3 Slab Concrete</span>
          <span class="wbs-badge ${sProgress >= 75 ? "completed" : sProgress >= 50 ? "ongoing" : "pending"}">${sProgress >= 75 ? "Completed" : sProgress >= 50 ? "In Progress" : "Pending"}</span>
        </div>
        <div class="wbs-task">
          <span class="wbs-bullet ${sProgress >= 100 ? "completed" : sProgress >= 75 ? "ongoing" : "pending"}"></span>
          <span>2.4 Staircase</span>
          <span class="wbs-badge ${sProgress >= 100 ? "completed" : sProgress >= 75 ? "ongoing" : "pending"}">${sProgress >= 100 ? "Completed" : sProgress >= 75 ? "In Progress" : "Pending"}</span>
        </div>
      </div>
    </div>

    <!-- Phase 3: Finishing -->
    <div class="wbs-phase active">
      <div class="wbs-phase-header" onclick="toggleWBSNode(this)">
        <span class="wbs-phase-arrow"><i class="bi bi-chevron-down"></i></span>
        <strong class="wbs-phase-title"><i class="bi bi-folder-fill text-gold"></i> 3. Finishing</strong>
        <div class="wbs-phase-meta">
          <span class="wbs-badge ${fiClass}">${fiStatus}</span>
          <span class="wbs-count">3 Tasks</span>
          <span class="wbs-pct">${fiProgress}%</span>
        </div>
      </div>
      <div class="wbs-phase-children">
        <div class="wbs-task">
          <span class="wbs-bullet ${fiProgress >= 30 ? "completed" : fiProgress > 0 ? "ongoing" : "pending"}"></span>
          <span>3.1 Plastering</span>
          <span class="wbs-badge ${fiProgress >= 30 ? "completed" : fiProgress > 0 ? "ongoing" : "pending"}">${fiProgress >= 30 ? "Completed" : fiProgress > 0 ? "In Progress" : "Pending"}</span>
        </div>
        <div class="wbs-task">
          <span class="wbs-bullet ${fiProgress >= 60 ? "completed" : fiProgress >= 30 ? "ongoing" : "pending"}"></span>
          <span>3.2 Painting</span>
          <span class="wbs-badge ${fiProgress >= 60 ? "completed" : fiProgress >= 30 ? "ongoing" : "pending"}">${fiProgress >= 60 ? "Completed" : fiProgress >= 30 ? "In Progress" : "Pending"}</span>
        </div>
        <div class="wbs-task">
          <span class="wbs-bullet ${fiProgress >= 100 ? "completed" : fiProgress >= 60 ? "ongoing" : "pending"}"></span>
          <span>3.3 Flooring</span>
          <span class="wbs-badge ${fiProgress >= 100 ? "completed" : fiProgress >= 60 ? "ongoing" : "pending"}">${fiProgress >= 100 ? "Completed" : fiProgress >= 60 ? "In Progress" : "Pending"}</span>
        </div>
      </div>
    </div>
  `;
}

function toggleWBSNode(header) {
  const phase = header.parentElement;
  phase.classList.toggle("active");
}

function renderGantt(project) {
  const headerRow = document.getElementById("ganttHeaderRow");
  const bodyContainer = document.getElementById("ganttBodyContainer");
  if (!headerRow || !bodyContainer) return;

  const start = project.start_date ? new Date(project.start_date) : new Date();
  const end = project.end_date ? new Date(project.end_date) : new Date(start.getTime() + 180 * 24 * 3600 * 1000);
  
  // Calculate months to show (up to 7 columns)
  const months = [];
  let curDate = new Date(start.getFullYear(), start.getMonth(), 1);
  const lastDate = new Date(end.getFullYear(), end.getMonth(), 1);
  
  while (curDate <= lastDate) {
    months.push({
      name: curDate.toLocaleDateString("en-US", { month: "short", year: "2-digit" }),
      year: curDate.getFullYear(),
      month: curDate.getMonth()
    });
    curDate.setMonth(curDate.getMonth() + 1);
  }
  
  if (months.length > 7) {
    months.length = 7;
  }
  
  // Render header
  let headerHtml = `<div class="gantt-title-col">Task Name</div>`;
  months.forEach(m => {
    headerHtml += `<div class="gantt-month-col">${m.name}</div>`;
  });
  headerRow.innerHTML = headerHtml;
  
  const projectStartMs = start.getTime();
  const projectEndMs = end.getTime();
  const projectDurationMs = projectEndMs - projectStartMs;
  
  function getGanttPosition(sDate, eDate) {
    const taskStartMs = sDate.getTime();
    const taskEndMs = eDate.getTime();
    
    const offsetLeft = Math.max(0, Math.min(100, ((taskStartMs - projectStartMs) / projectDurationMs) * 100));
    const offsetWidth = Math.max(0, Math.min(100 - offsetLeft, ((taskEndMs - taskStartMs) / projectDurationMs) * 100));
    
    return { left: offsetLeft, width: offsetWidth };
  }
  
  const stages = [
    { name: "Foundation", ratioStart: 0, ratioEnd: 0.15 },
    { name: "Structure", ratioStart: 0.15, ratioEnd: 0.50 },
    { name: "Plastering", ratioStart: 0.50, ratioEnd: 0.70 },
    { name: "Painting", ratioStart: 0.70, ratioEnd: 0.80 },
    { name: "Flooring", ratioStart: 0.80, ratioEnd: 0.90 },
    { name: "Finishing", ratioStart: 0.90, ratioEnd: 1.0 }
  ];
  
  const progress = Number(project.progress || 0);
  let bodyHtml = "";
  
  stages.forEach((stage, idx) => {
    const stageStartMs = projectStartMs + projectDurationMs * stage.ratioStart;
    const stageEndMs = projectStartMs + projectDurationMs * stage.ratioEnd;
    const sDate = new Date(stageStartMs);
    const eDate = new Date(stageEndMs);
    
    const pos = getGanttPosition(sDate, eDate);
    const dateStr = `${sDate.getDate()} ${sDate.toLocaleString('en-US', {month:'short'})} - ${eDate.getDate()} ${eDate.toLocaleString('en-US', {month:'short'})}`;
    
    let stateClass = "pending";
    if (progress >= stage.ratioEnd * 100) {
      stateClass = "completed";
    } else if (progress > stage.ratioStart * 100) {
      stateClass = "ongoing";
    } else if (idx === 2 || idx === 3) {
      stateClass = "upcoming";
    }
    
    bodyHtml += `
      <div class="gantt-row">
        <div class="gantt-task-name">${stage.name}</div>
        <div class="gantt-timeline-area">
          ${months.map(() => `<div class="gantt-col-line"></div>`).join("")}
          <div class="gantt-bar-fill ${stateClass}" style="left: ${pos.left}%; width: ${pos.width}%;">
            <span class="gantt-bar-label">${dateStr}</span>
          </div>
        </div>
      </div>
    `;
  });
  
  // Vertical today indicator
  const today = new Date();
  if (today.getTime() >= projectStartMs && today.getTime() <= projectEndMs) {
    const todayPct = ((today.getTime() - projectStartMs) / projectDurationMs) * 100;
    bodyHtml += `
      <div class="gantt-today-line" style="left: calc(150px + ${todayPct}% * (100% - 150px) / 100)">
        <span class="gantt-today-label">Today</span>
      </div>
    `;
  }
  
  bodyContainer.innerHTML = bodyHtml;
}

function renderMilestones(project) {
  const container = document.getElementById("upcomingMilestonesContainer");
  if (!container) return;

  const startDate = project.start_date ? new Date(project.start_date) : null;
  const endDate = project.end_date ? new Date(project.end_date) : null;
  
  if (!startDate || !endDate || Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
    container.innerHTML = '<div class="empty-state p-3 text-center text-muted">No milestone schedule available.</div>';
    return;
  }

  const durationMs = endDate.getTime() - startDate.getTime();
  const steps = [
    { title: "Foundation Work Start", ratio: 0 },
    { title: "Structure Work Start", ratio: 0.15 },
    { title: "Plastering Work Start", ratio: 0.50 },
    { title: "Flooring Work Start", ratio: 0.80 },
    { title: "Finishing Work Start", ratio: 0.90 }
  ];

  const today = new Date();
  const upcoming = steps.filter(step => {
    const stepDate = addDays(startDate, Math.round((durationMs / 86400000) * step.ratio));
    return stepDate && stepDate >= today;
  });

  if (!upcoming.length) {
    container.innerHTML = '<div class="empty-state p-3 text-center text-muted">All milestones reached.</div>';
    return;
  }

  container.innerHTML = upcoming.slice(0, 3).map(step => {
    const stepDate = addDays(startDate, Math.round((durationMs / 86400000) * step.ratio));
    const daysRemaining = Math.max(0, Math.round((stepDate.getTime() - today.getTime()) / 86400000));
    
    return `
      <div class="milestone-item-row">
        <div class="milestone-details">
          <strong>${escapeHtml(step.title)}</strong>
          <span>${formatDateFromDate(stepDate)}</span>
        </div>
        <span class="days-left-tag">In ${daysRemaining} Days</span>
      </div>
    `;
  }).join("");
}

function renderProfitability(project) {
  const materialCost = Number(project.material_cost || 0);
  const labourCost = Number(project.labour_cost || 0);
  const otherCost = Number(project.other_cost || 0);
  const netProfit = Number(project.net_profit || 0);
  const profitPercent = Number(project.profit_percent || 0);
  const amountReceived = Number(project.amount_received || 0);
  const budget = Number(project.budget || 0);
  
  // Text content bindings
  setText("profContractValue", formatCurrency(budget));
  setText("profMaterialCost", formatCurrency(materialCost));
  setText("profLabourCost", formatCurrency(labourCost));
  setText("profOtherCost", formatCurrency(otherCost));
  setText("profTotalExpense", formatCurrency(materialCost + labourCost + otherCost));
  setText("profReceived", formatCurrency(amountReceived));
  setText("profNetProfit", formatCurrency(netProfit));
  setText("profProfitPercent", `${profitPercent.toFixed(2)}%`);
  setText("profitabilityNetValue", formatCurrency(netProfit));
  
  // Donut chart drawing
  const donutChart = document.getElementById("profitabilityDonutChart");
  if (!donutChart) return;
  
  const totalSum = materialCost + labourCost + otherCost + Math.max(0, netProfit);
  if (totalSum > 0) {
    const matDeg = (materialCost / totalSum) * 360;
    const labDeg = (labourCost / totalSum) * 360;
    const othDeg = (otherCost / totalSum) * 360;
    
    donutChart.style.background = `conic-gradient(
      #9333ea 0deg ${matDeg}deg,
      #3b82f6 ${matDeg}deg ${matDeg + labDeg}deg,
      #ea580c ${matDeg + labDeg}deg ${matDeg + labDeg + othDeg}deg,
      #16a34a ${matDeg + labDeg + othDeg}deg 360deg
    )`;
  } else {
    donutChart.style.background = '#e2e8f0';
  }
}

function renderDailyReports(project) {
  const container = document.getElementById("dailyReportsContainer");
  if (!container) return;

  fetch("get_updates.php")
    .then(res => res.json())
    .then(updates => {
      const projTitle = (project.title || "").toLowerCase();
      const projectUpdates = updates.filter(u => {
        const titleText = (u.title || "").toLowerCase();
        const descText = (u.description || "").toLowerCase();
        return titleText.includes(projTitle) || descText.includes(projTitle);
      });

      if (!projectUpdates.length) {
        container.innerHTML = `<div class="empty-state p-3 text-center text-muted">No reports submitted for this project.</div>`;
        return;
      }

      container.innerHTML = projectUpdates.slice(0, 3).map(u => {
        const date = new Date(u.created_at);
        const day = date.getDate().toString().padStart(2, '0');
        const month = date.toLocaleString('en-US', { month: 'short' }).toUpperCase();
        
        return `
          <div class="report-item-card">
            <div class="report-date-badge">
              <span class="day">${day}</span>
              <span class="month">${month}</span>
            </div>
            <div class="report-item-details">
              <strong>${escapeHtml(u.title)}</strong>
              <span>By: Site Engineer (System)</span>
            </div>
            <span class="report-status-badge completed">${escapeHtml(u.category || 'PROGRESS')}</span>
          </div>
        `;
      }).join("");
    })
    .catch(() => {
      container.innerHTML = `<div class="empty-state p-3 text-center text-muted">Unable to load reports.</div>`;
    });
}

function renderSitePhotos(project) {
  const container = document.getElementById("sitePhotosContainer");
  if (!container) return;

  const imgUrl = project.image || "https://via.placeholder.com/600x400?text=JGC+Constructions";
  
  container.innerHTML = `
    <div class="photo-thumbnail">
      <img src="${escapeHtml(imgUrl)}" alt="Site Photo 1">
    </div>
    <div class="photo-thumbnail placeholder-thumb">
      <div class="placeholder-icon"><i class="bi bi-camera"></i></div>
      <span class="todo-tag">TODO: Upload</span>
    </div>
    <div class="photo-thumbnail placeholder-thumb">
      <div class="placeholder-icon"><i class="bi bi-camera"></i></div>
      <span class="todo-tag">TODO: Upload</span>
    </div>
    <div class="photo-thumbnail placeholder-thumb">
      <div class="placeholder-icon"><i class="bi bi-camera"></i></div>
      <span class="todo-tag">TODO: Upload</span>
    </div>
  `;
}

function renderOpenIssues(project) {
  const container = document.getElementById("openIssuesContainer");
  if (!container) return;

  const issues = [
    { priority: "High", title: "Material Delay", assigned: "Purchase Team", status: "Open", class: "high" },
    { priority: "Medium", title: "Labour Shortage", assigned: "Site Engineer", status: "Open", class: "medium" },
    { priority: "Low", title: "Drawing Clarification", assigned: "Design Team", status: "Open", class: "low" }
  ];

  container.innerHTML = issues.map(issue => `
    <div class="issue-item-card">
      <div class="issue-main">
        <span class="priority-badge ${issue.class}">${issue.priority}</span>
        <strong>${escapeHtml(issue.title)}</strong>
        <span>Assigned: ${escapeHtml(issue.assigned)}</span>
      </div>
      <span class="issue-status-badge">${issue.status}</span>
    </div>
  `).join("");
}

/* 🔹 LEGACY FALLBACKS FOR COMPATIBILITY */
function renderWorkers(workers) {}
function renderMaterials(materials) {}
function renderWorkStatus(project) {}
function renderRisks(project) {}
function renderNotes(project) {}
function renderQualityChecklist(project) {}
function renderSafetyStatus(project) {}
function renderAttachments(project) {}

function downloadProjectSummary(project) {
  if (!window.jspdf || !window.jspdf.jsPDF) {
    alert("PDF library failed to load. Please refresh and try again.");
    return;
  }

  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ unit: "pt", format: "a4" });
  const lineHeight = 22;
  let y = 56;

  const title = project.title || "Project";
  const projectCode = project.project_code || "-";

  doc.setFont("helvetica", "bold");
  doc.setFontSize(18);
  doc.text("Project Summary Report", 40, y);

  y += 28;
  doc.setFont("helvetica", "normal");
  doc.setFontSize(11);
  doc.text(`Generated: ${new Date().toLocaleString("en-IN")}`, 40, y);

  y += 30;
  doc.setFont("helvetica", "bold");
  doc.setFontSize(13);
  doc.text(`${title} (${projectCode})`, 40, y);

  const rows = [
    ["Location", `${project.city || "-"}, Tamil Nadu`],
    ["Address", project.address || "-"],
    ["Start Date", formatDate(project.start_date)],
    ["End Date", formatDate(project.end_date)],
    ["Status", project.status || "-"],
    ["Progress", `${project.progress || 0}%`],
    ["Budget", `INR ${Number(project.budget || 0).toLocaleString("en-IN")}`],
    ["Expense", `INR ${Number(project.expense || 0).toLocaleString("en-IN")}`],
    ["Remaining Budget", `INR ${Number(project.remaining_budget || 0).toLocaleString("en-IN")}`],
    ["Workers", String(project.worker_count || 0)],
    ["Materials", String(project.material_count || 0)],
    ["Duration", `${project.duration_days || 0} Days`],
    ["Days Remaining", `${project.days_remaining || 0} Days`]
  ];

  y += 24;
  rows.forEach(([label, value]) => {
    if (y > 780) {
      doc.addPage();
      y = 56;
    }

    doc.setFont("helvetica", "bold");
    doc.setFontSize(11);
    doc.text(`${label}:`, 40, y);

    doc.setFont("helvetica", "normal");
    doc.text(String(value), 180, y);
    y += lineHeight;
  });

  doc.save(`${(projectCode || "project").toLowerCase()}-summary.pdf`);
}

function loadProject(projectId) {
  fetch(`get_project.php?id=${encodeURIComponent(projectId)}`)
    .then(response => response.json())
    .then(data => {
      if (!data || data.error || !data.project) {
        setText("projectBreadcrumb", "Project not found");
        return;
      }

      const project = data.project;
      window.currentProjectSnapshot = project;
      const image = project.image || "https://via.placeholder.com/1200x800?text=Project";
      const progress = Number(project.progress || 0);
      const budget = Number(project.budget || 0);
      const expense = Number(project.expense || 0);
      const remainingBudget = Number(project.remaining_budget || 0);
      
      const plannedProgress = Math.max(0, Math.min(100, project.duration_days ? Math.round(((project.duration_days - project.days_remaining) / project.duration_days) * 100) : progress));

      // Circular Ring
      const completionProgress = document.getElementById("completionCircularProgress");
      if (completionProgress) {
        completionProgress.style.background = `conic-gradient(var(--primary-gold) ${progress * 3.6}deg, #e2e8f0 0deg)`;
      }

      document.getElementById("projectHeroImage").src = image;
      document.getElementById("projectHeroImage").alt = project.title || "Project image";
      
      setText("projectStatusPill", project.status || "Active");
      setText("projectTitle", project.title || "Untitled Project");
      setText("projectLocation", `${project.city || "Unknown"}${project.city ? ", Tamil Nadu" : ""}`);
      setText("projectStart", formatDate(project.start_date));
      setText("projectEnd", formatDate(project.end_date));
      
      // Delay alert
      const delayAlert = document.getElementById("projectDelayAlert");
      if (delayAlert) {
        if (project.days_remaining < 0) {
          delayAlert.textContent = `Delayed by ${Math.abs(project.days_remaining)} Days`;
          delayAlert.className = "meta-delay-alert delayed";
        } else {
          delayAlert.textContent = "On Track";
          delayAlert.className = "meta-delay-alert on-track";
        }
      }

      // Project Manager lookup in workers list
      let managerName = "Arun Kumar";
      let managerPhone = "+91 98765 43210";
      if (project.workers && Array.isArray(project.workers)) {
        const mgr = project.workers.find(w => {
          const role = (w.type || "").toLowerCase();
          return role.includes("manager") || role.includes("engineer") || role.includes("admin");
        });
        if (mgr) {
          managerName = mgr.name;
          managerPhone = mgr.phone || managerPhone;
        }
      }
      setText("projectManagerName", managerName);
      setText("projectManagerPhone", managerPhone);

      // KPI Summary bindings
      setText("kpiProjectValue", formatCurrency(budget));
      setText("kpiReceived", formatCurrency(project.amount_received));
      setText("kpiPending", formatCurrency(project.amount_pending));
      setText("kpiMaterialCost", formatCurrency(project.material_cost));
      setText("kpiLabourCost", formatCurrency(project.labour_cost));
      setText("kpiProfitMargin", formatCurrency(project.net_profit));
      setText("kpiProgress", `${progress}%`);
      document.getElementById("kpiProgressFill").style.width = `${progress}%`;

      // Legend variables
      const ongoingVal = Math.min(20, 100 - progress);
      const pendingVal = Math.max(0, 100 - progress - ongoingVal);
      const notStartedVal = 100 - progress - ongoingVal - pendingVal;
      
      setText("circularProgressText", `${progress}%`);
      setText("legendCompletedVal", `${progress}%`);
      setText("legendOngoingVal", `${ongoingVal}%`);
      setText("legendPendingVal", `${pendingVal}%`);
      setText("legendNotStartedVal", `${notStartedVal}%`);

      setText("projectBreadcrumb", `${project.title || "Project"} · ${project.project_code || "-"}`);

      // New Widgets
      renderWBS(project);
      renderGantt(project);
      renderMilestones(project);
      renderProfitability(project);
      renderDailyReports(project);
      renderSitePhotos(project);
      renderOpenIssues(project);

      // Actions bindings
      const downloadBtn = document.getElementById("downloadSummaryBtn");
      if (downloadBtn) {
        downloadBtn.onclick = () => downloadProjectSummary(project);
      }

      document.getElementById("projectIdField").value = project.id || "";
      document.getElementById("editTitle").value = project.title || "";
      document.getElementById("editCity").value = project.city || "";
      document.getElementById("editAddress").value = project.address || "";
      document.getElementById("editStart").value = project.start_date || "";
      document.getElementById("editEnd").value = project.end_date || "";
      document.getElementById("editImage").value = "";
    })
    .catch(() => {
      setText("projectBreadcrumb", "Unable to load project details");
    });
}

function saveProjectChanges(event) {
  event.preventDefault();

  const id = document.getElementById("projectIdField").value;
  const title = document.getElementById("editTitle").value.trim();
  const city = document.getElementById("editCity").value.trim();
  const address = document.getElementById("editAddress").value.trim();
  const start = document.getElementById("editStart").value;
  const end = document.getElementById("editEnd").value;
  const file = document.getElementById("editImage").files[0];

  if (!id || !title || !city || !address || !start || !end) {
    alert("Fill all fields before saving.");
    return;
  }

  const formData = new FormData();
  formData.append("id", id);
  formData.append("title", title);
  formData.append("city", city);
  formData.append("address", address);
  formData.append("start", start);
  formData.append("end", end);

  if (file) {
    formData.append("image", file);
  }

  fetch("update_project.php", {
    method: "POST",
    body: formData
  })
    .then(response => response.text())
    .then(result => {
      if (result.trim() === "success") {
        alert("Project updated");
        loadProject(id);
      } else {
        alert(result);
      }
    });
}

document.addEventListener("DOMContentLoaded", () => {
  const projectId = getProjectIdFromQuery();

  if (!projectId) {
    setText("projectBreadcrumb", "Missing project id");
    return;
  }

  document.getElementById("scrollEditBtn").addEventListener("click", scrollToEditSection);
  document.getElementById("reloadProjectBtn").addEventListener("click", () => loadProject(projectId));
  document.getElementById("cancelEditBtn").addEventListener("click", () => loadProject(projectId));
  document.getElementById("projectEditForm").addEventListener("submit", saveProjectChanges);

  loadProject(projectId);
});