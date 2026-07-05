let projects = [];
let workers = [];
let totalCount = 0;
let currentIndex = 0;
let activeWorkerId = null;
let activeTab = "attendance";

// const data = {
//   project_id: 1,   // or dynamic
//   type: "worker",

//   name: document.getElementById("name").value,
//   phone: document.getElementById("phone").value,
//   role: document.getElementById("role").value,

//   wage_type: document.getElementById("wage_type").value,
//   wage_rate: document.getElementById("wage_rate").value,
//   working_hours: document.getElementById("working_hours").value,

//   in_time: document.getElementById("in_time").value,
//   out_time: document.getElementById("out_time").value,

//   ot_rate: document.getElementById("ot_rate").value,
//   ot_limit: document.getElementById("ot_limit").value
// };

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function getProjectTitleById(projectId) {
  const project = projects.find(item => String(item.id) === String(projectId));
  return project ? (project.title || project.name || "Unknown Site") : "Unknown Site";
}

function getWorkerRole(worker) {
  if (worker.extra && String(worker.extra).trim() !== "") {
    return worker.extra;
  }

  if (!worker.type) {
    return "Worker";
  }

  return worker.type.charAt(0).toUpperCase() + worker.type.slice(1);
}

function isPresent(worker) {
  return Number(worker.shift_am) === 1 || Number(worker.shift_pm) === 1;
}

function getFilteredWorkers() {
  const searchValue = document.getElementById("workerSearch")?.value.toLowerCase().trim() || "";
  const siteValue = document.getElementById("siteFilter")?.value || "all";
  const statusValue = document.getElementById("statusFilter")?.value || "all";

  return workers.filter(worker => {
    const title = getProjectTitleById(worker.project_id);
    const role = getWorkerRole(worker);
    const status = isPresent(worker) ? "present" : "absent";

    const matchesSearch = !searchValue || [worker.name, worker.phone, role, title, worker.type]
      .join(" ")
      .toLowerCase()
      .includes(searchValue);

    const matchesSite = siteValue === "all" || String(worker.project_id) === String(siteValue);
    const matchesStatus = statusValue === "all" || status === statusValue;

    return matchesSearch && matchesSite && matchesStatus;
  });
}

function switchTab(tab) {
  activeTab = tab;

  document.querySelectorAll(".section-tab").forEach(button => {
    button.classList.toggle("active", button.dataset.tab === tab);
  });

  document.querySelectorAll(".tab-panel").forEach(panel => panel.classList.remove("active"));
  const panel = document.getElementById(`${tab}Panel`);
  if (panel) {
    panel.classList.add("active");
  }
}

function renderStats(filteredWorkers) {
  const container = document.getElementById("workerStats");
  if (!container) return;

  const total = filteredWorkers.length;
  const present = filteredWorkers.filter(isPresent).length;
  const shiftAm = filteredWorkers.filter(worker => Number(worker.shift_am) === 1).length;
  const shiftPm = filteredWorkers.filter(worker => Number(worker.shift_pm) === 1).length;

  container.innerHTML = `
    <div class="col-12 col-md-6 col-xl-3">
      <div class="worker-stat-card">
        <div class="worker-stat-inner">
          <div class="worker-stat-copy">
            <h3>${total}</h3>
            <p>Total Workers</p>
          </div>
          <div class="worker-stat-icon icon-blue"><i class="bi bi-people"></i></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="worker-stat-card">
        <div class="worker-stat-inner">
          <div class="worker-stat-copy">
            <h3>${present}</h3>
            <p>Present Today</p>
          </div>
          <div class="worker-stat-icon icon-green"><i class="bi bi-check2-circle"></i></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="worker-stat-card">
        <div class="worker-stat-inner">
          <div class="worker-stat-copy">
            <h3>${shiftAm}</h3>
            <p>AM Shifts</p>
          </div>
          <div class="worker-stat-icon icon-amber"><i class="bi bi-sunrise"></i></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
      <div class="worker-stat-card">
        <div class="worker-stat-inner">
          <div class="worker-stat-copy">
            <h3>${shiftPm}</h3>
            <p>PM Shifts</p>
          </div>
          <div class="worker-stat-icon icon-red"><i class="bi bi-moon"></i></div>
        </div>
      </div>
    </div>
  `;
}

function renderAttendanceTable(filteredWorkers) {
  const container = document.getElementById("attendanceTableBody");
  if (!container) return;

  if (filteredWorkers.length === 0) {
    container.innerHTML = `
      <tr>
        <td colspan="8" class="text-center text-muted py-4">No workers match the current filters.</td>
      </tr>
    `;
    return;
  }

  container.innerHTML = filteredWorkers.map((worker, index) => {
    const present = isPresent(worker);
    const role = getWorkerRole(worker);
    const siteTitle = getProjectTitleById(worker.project_id);
    const initials = String(worker.name || "W").trim().charAt(0) || "W";

    return `
      <tr>
        <td>${index + 1}</td>
        <td>
          <div class="worker-name">
            <div class="avatar-dot">${escapeHtml(initials)}</div>
            <div>
              <strong>${escapeHtml(worker.name || "Unnamed Worker")}</strong>
              <small>${escapeHtml(worker.phone || "No phone added")}</small>
            </div>
          </div>
        </td>
        <td>${escapeHtml(role)}</td>
        <td>${escapeHtml(siteTitle)}</td>
        <td>
          <button class="shift-chip ${Number(worker.shift_am) === 1 ? "active" : ""}" type="button" onclick="toggleShift(${worker.id}, 'am')">
            ${Number(worker.shift_am) === 1 ? "Present" : "Mark"}
          </button>
        </td>
        <td>
          <button class="shift-chip ${Number(worker.shift_pm) === 1 ? "active" : ""}" type="button" onclick="toggleShift(${worker.id}, 'pm')">
            ${Number(worker.shift_pm) === 1 ? "Present" : "Mark"}
          </button>
        </td>
        <td>
          <span class="status-pill ${present ? "status-present" : "status-absent"}">
            <i class="bi ${present ? "bi-check-circle" : "bi-slash-circle"}"></i>
            ${present ? "Present" : "Absent"}
          </span>
        </td>
        <td>
          <div class="action-group">
            <button class="icon-action-btn primary" type="button" title="Manage attendance" onclick="openAttendance(${worker.id})">
              <i class="bi bi-pencil-square"></i>
            </button>
            <button class="icon-action-btn danger" type="button" title="Delete worker" onclick="deleteWorker(${worker.id})">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join("");
}

function renderWorkersTable(filteredWorkers) {
  const container = document.getElementById("workersTableBody");
  if (!container) return;

  if (filteredWorkers.length === 0) {
    container.innerHTML = `
      <tr>
        <td colspan="8" class="text-center text-muted py-4">No workers found.</td>
      </tr>
    `;
    return;
  }

  container.innerHTML = filteredWorkers.map((worker, index) => {
    const present = isPresent(worker);
    const role = getWorkerRole(worker);
    const siteTitle = getProjectTitleById(worker.project_id);
    const initials = String(worker.name || "W").trim().charAt(0) || "W";

    return `
      <tr>
        <td>${index + 1}</td>
        <td>
          <div class="worker-name">
            <div class="avatar-dot">${escapeHtml(initials)}</div>
            <div>
              <strong>${escapeHtml(worker.name || "Unnamed Worker")}</strong>
              <small>${escapeHtml(worker.extra || "No role details")}</small>
            </div>
          </div>
        </td>
        <td>${escapeHtml(worker.phone || "-")}</td>
        <td>${escapeHtml(role)}</td>
        <td>${escapeHtml(siteTitle)}</td>
        <td>${escapeHtml(worker.type || "-")}</td>
        <td>
          <span class="status-pill ${present ? "status-present" : "status-absent"}">
            ${present ? "Active" : "Idle"}
          </span>
        </td>
        <td>
          <div class="action-group">
            <button class="icon-action-btn primary" type="button" title="Manage attendance" onclick="openAttendance(${worker.id})">
              <i class="bi bi-people"></i>
            </button>
            <button class="icon-action-btn danger" type="button" title="Delete worker" onclick="deleteWorker(${worker.id})">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join("");
}

function renderProjectFilter() {
  const select = document.getElementById("siteFilter");
  if (!select) return;

  const currentValue = select.value || "all";
  select.innerHTML = `<option value="all">All Sites</option>`;

  projects.forEach(project => {
    select.innerHTML += `<option value="${project.id}">${escapeHtml(project.title || project.name || "Untitled Site")}</option>`;
  });

  select.value = currentValue;
}

function applyFilters() {
  const filteredWorkers = getFilteredWorkers();
  renderStats(filteredWorkers);
  renderAttendanceTable(filteredWorkers);
  renderWorkersTable(filteredWorkers);
}

function loadWorkersPageData() {
  return Promise.all([
    fetch("get_projects.php").then(response => response.json()),
    fetch("get_workers.php").then(response => response.json())
  ]).then(([projectData, workerData]) => {
    projects = Array.isArray(projectData) ? projectData : [];
    workers = Array.isArray(workerData) ? workerData : [];
    renderProjectFilter();
    applyFilters();
    loadProjectList();
  }).catch(error => {
    console.error("Failed to load worker data:", error);
  });
}

function openStep1() {
  document.getElementById("step1").classList.add("active");
}

function closeAll() {
  document.querySelectorAll(".step").forEach(step => step.classList.remove("active"));

  const singleForm = document.getElementById("singleForm");
  if (singleForm) {
    singleForm.style.display = "none";
  }

  const count = document.getElementById("count");
  const name = document.getElementById("name");
  const phone = document.getElementById("phone");
  const extra = document.getElementById("extra");
  const phoneError = document.getElementById("phoneError");

  if (count) count.value = "";
  if (name) name.value = "";
  if (phone) phone.value = "";
  if (extra) extra.value = "";
  if (phoneError) phoneError.textContent = "";
}

function closeAttendance() {
  document.getElementById("attendanceModal").classList.remove("active");
}

function selectProject(id) {
  window.selectedProjectId = id;
  closeAll();
  document.getElementById("step2").classList.add("active");
}

function selectType(type) {
  window.selectedType = type;
  closeAll();
  document.getElementById("step3").classList.add("active");
}

function updateStepTitle() {
  document.getElementById("stepTitle").innerText = `Add ${window.selectedType} (${currentIndex}/${totalCount})`;
}

function startAdding() {
  totalCount = parseInt(document.getElementById("count").value, 10);

  if (!totalCount || totalCount < 1) {
    return;
  }

  currentIndex = 1;
  document.getElementById("singleForm").style.display = "block";
  updateStepTitle();
}

function clearSingleForm() {
  document.getElementById("name").value = "";
  document.getElementById("phone").value = "";
  document.getElementById("extra").value = "";
  document.getElementById("phoneError").textContent = "";
}

function saveWorker() {

  const data = {

    project_id: window.selectedProjectId,   // ✅ REQUIRED
    type: window.selectedType,              // ✅ REQUIRED

    name: document.getElementById("name").value,
    phone: document.getElementById("phone").value,
    role: document.getElementById("role").value,

    wage_type: document.getElementById("wage_type").value,
    wage_rate: document.getElementById("wage_rate").value,
    working_hours: document.getElementById("working_hours").value,

    in_time: document.getElementById("in_time").value,
    out_time: document.getElementById("out_time").value,

    ot_rate: document.getElementById("ot_rate").value,
    ot_limit: document.getElementById("ot_limit").value
  };

  fetch("add_worker.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data)
  })
  .then(res => res.json())
  .then(res => {
    alert("Worker Added!");
    location.reload();
  });
}

function loadProjectList() {
  const list = document.getElementById("projectList");
  if (!list) return;

  list.className = "project-picker";
  list.innerHTML = "";

  projects.forEach(project => {
    list.innerHTML += `
      <button type="button" onclick="selectProject(${project.id})">
        ${escapeHtml(project.title || project.name || "Untitled Site")}
      </button>
    `;
  });
}

function openAttendance(workerId) {
  const worker = workers.find(item => String(item.id) === String(workerId));
  if (!worker) return;

  activeWorkerId = worker.id;
  document.getElementById("attendanceModal").classList.add("active");

  const role = getWorkerRole(worker);
  const site = getProjectTitleById(worker.project_id);
  const present = isPresent(worker);
  const attendanceList = document.getElementById("attendanceList");

  attendanceList.innerHTML = `
    <div class="attendance-worker-card">
      <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
          <h6 class="mb-1">${escapeHtml(worker.name || "Unnamed Worker")}</h6>
          <div class="meta">${escapeHtml(role)} · ${escapeHtml(site)}</div>
          <div class="meta">${escapeHtml(worker.phone || "No phone added")}</div>
        </div>
        <span class="status-pill ${present ? "status-present" : "status-absent"}">
          ${present ? "Present" : "Absent"}
        </span>
      </div>

      <div class="attendance-switches mt-3">
        <button class="toggle-attendance ${Number(worker.shift_am) === 1 ? "active" : ""}" type="button" onclick="toggleShift(${worker.id}, 'am')">
          <span>Morning Shift</span>
          <i class="bi ${Number(worker.shift_am) === 1 ? "bi-check-circle-fill" : "bi-circle"}"></i>
        </button>

        <button class="toggle-attendance ${Number(worker.shift_pm) === 1 ? "active" : ""}" type="button" onclick="toggleShift(${worker.id}, 'pm')">
          <span>Evening Shift</span>
          <i class="bi ${Number(worker.shift_pm) === 1 ? "bi-check-circle-fill" : "bi-circle"}"></i>
        </button>
      </div>
    </div>
  `;
}

function toggleShift(id, shift) {
  const formData = new FormData();
  formData.append("id", id);
  formData.append("shift", shift);

  fetch("toggle_shift.php", {
    method: "POST",
    body: formData
  }).then(() => {
    loadWorkersPageData().then(() => {
      if (activeWorkerId && String(activeWorkerId) === String(id) && document.getElementById("attendanceModal").classList.contains("active")) {
        openAttendance(id);
      }
    });

    addSystemUpdate(
      "Attendance Updated",
      "Worker shift updated",
      "PROGRESS"
    );
  });
}

function deleteWorker(id) {
  const worker = workers.find(item => String(item.id) === String(id));
  const workerName = worker ? worker.name : "this worker";

  if (!window.confirm(`Delete ${workerName}?`)) {
    return;
  }

  const formData = new FormData();
  formData.append("id", id);

  fetch("delete_worker.php", {
    method: "POST",
    body: formData
  })
    .then(response => response.text())
    .then(result => {
      if (result === "success") {
        addSystemUpdate(
          "Worker Removed",
          `${workerName} deleted`,
          "WARNING"
        );
        loadWorkersPageData();
        closeAttendance();
      }
    });
}

document.addEventListener("DOMContentLoaded", () => {
  loadWorkersPageData();
});
