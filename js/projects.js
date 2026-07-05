const projectState = {
  allProjects: [],
  search: "",
  status: "all",
  location: "all",
  sortBy: "newest",
  view: "list",
  page: 1,
  pageSize: 2
};

let editId = null;

function escapeHtml(value) {
  const text = String(value || "");
  return text
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function toDate(dateText) {
  if (!dateText) return null;
  const date = new Date(dateText);
  return Number.isNaN(date.getTime()) ? null : date;
}

function formatLongDate(dateText) {
  const date = toDate(dateText);
  if (!date) return "-";
  return date.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric"
  });
}

function getProjectStatus(project) {
  const now = new Date();
  const endDate = toDate(project.end_date);
  if (endDate && now > endDate) return "completed";
  return "active";
}

function getProgress(project) {
  const startDate = toDate(project.start_date);
  const endDate = toDate(project.end_date);
  if (!startDate || !endDate || endDate <= startDate) return 0;

  const now = new Date();
  if (now <= startDate) return 0;
  if (now >= endDate) return 100;

  const elapsed = now.getTime() - startDate.getTime();
  const total = endDate.getTime() - startDate.getTime();
  return Math.round((elapsed / total) * 100);
}

function setupLocationFilter(projects) {
  const locationFilter = document.getElementById("locationFilter");
  if (!locationFilter) return;

  const uniqueCities = [...new Set(projects.map(p => (p.city || "").trim()).filter(Boolean))]
    .sort((a, b) => a.localeCompare(b));

  locationFilter.innerHTML = '<option value="all">All Locations</option>';
  uniqueCities.forEach(city => {
    const option = document.createElement("option");
    option.value = city.toLowerCase();
    option.textContent = city;
    locationFilter.appendChild(option);
  });
}

function applyFilters(projects) {
  const search = projectState.search.trim().toLowerCase();

  const result = projects.filter(project => {
    const status = getProjectStatus(project);
    const city = (project.city || "").toLowerCase();
    const title = (project.title || "").toLowerCase();
    const address = (project.address || "").toLowerCase();

    const matchesSearch = !search
      || title.includes(search)
      || city.includes(search)
      || address.includes(search);

    const matchesStatus = projectState.status === "all" || status === projectState.status;
    const matchesLocation = projectState.location === "all" || city === projectState.location;

    return matchesSearch && matchesStatus && matchesLocation;
  });

  result.sort((a, b) => {
    const aStart = toDate(a.start_date)?.getTime() || 0;
    const bStart = toDate(b.start_date)?.getTime() || 0;
    const aTitle = (a.title || "").toLowerCase();
    const bTitle = (b.title || "").toLowerCase();

    switch (projectState.sortBy) {
      case "oldest":
        return aStart - bStart;
      case "name_asc":
        return aTitle.localeCompare(bTitle);
      case "name_desc":
        return bTitle.localeCompare(aTitle);
      case "newest":
      default:
        return bStart - aStart;
    }
  });

  return result;
}

function getPagedData(data) {
  const total = data.length;
  const pageCount = Math.max(1, Math.ceil(total / projectState.pageSize));

  if (projectState.page > pageCount) {
    projectState.page = pageCount;
  }

  const start = (projectState.page - 1) * projectState.pageSize;
  const end = start + projectState.pageSize;

  return {
    total,
    pageCount,
    paged: data.slice(start, end),
    start: total === 0 ? 0 : start + 1,
    end: Math.min(end, total)
  };
}

function projectCardTemplate(project) {
  const status = getProjectStatus(project);
  const statusLabel = status === "active" ? "Active" : "Completed";
  const progress = getProgress(project);
  const projectCode = project.project_code || `PRJ${String(project.id || 0).padStart(4, "0")}`;
  const imageUrl = project.image || "https://via.placeholder.com/800x500?text=Project";
  const updated = formatLongDate(project.updated_at || project.start_date);
  const detailUrl = `project_details.php?id=${encodeURIComponent(project.id)}`;

  return `
    <article class="project-card">
      <a class="project-card-link" href="${detailUrl}" aria-label="Open ${escapeHtml(project.title || "Project")} details"></a>
      <div class="project-image-wrap">
        <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(project.title || "Project")}">
        <span class="status-pill-floating">${statusLabel}</span>
      </div>

      <div class="project-main">
        <h3 class="project-title">${escapeHtml(project.title || "Untitled Project")}</h3>

        <div class="meta-line">
          <span><i class="bi bi-geo-alt"></i> ${escapeHtml(project.city || "Unknown")}, Tamil Nadu</span>
        </div>

        <div class="sub-meta">
          <span><i class="bi bi-fingerprint"></i> Project ID: ${projectCode}</span>
          <span><i class="bi bi-buildings"></i> Construction Project</span>
        </div>

        <div class="progress-title">Progress <span class="progress-value">${progress}%</span></div>
        <div class="project-progress">
          <div class="fill" style="width: ${progress}%;"></div>
        </div>
        <div class="updated-text">Last Updated: ${updated}</div>
      </div>

      <div class="project-side">
        <span class="status-pill">${statusLabel}</span>

        <div class="date-block">
          <div class="date-item">
            <small>Start Date</small>
            <span><i class="bi bi-calendar-event"></i> ${formatLongDate(project.start_date)}</span>
          </div>
          <div class="date-item">
            <small>End Date</small>
            <span><i class="bi bi-calendar-check"></i> ${formatLongDate(project.end_date)}</span>
          </div>
        </div>

        <div class="project-actions">
          <button class="btn-soft btn-view" onclick="event.stopPropagation(); window.location.href='${detailUrl}'"><i class="bi bi-eye"></i> View</button>
          <button class="btn-soft btn-edit" onclick="event.stopPropagation(); editProject(${project.id})"><i class="bi bi-pencil"></i> Edit</button>
          <button class="btn-soft btn-delete" onclick="event.stopPropagation(); deleteProject(${project.id})"><i class="bi bi-trash"></i> Delete</button>
        </div>
      </div>
    </article>
  `;
}

function renderPagination(pageCount) {
  const pagination = document.getElementById("projectsPagination");
  if (!pagination) return;

  if (pageCount <= 1) {
    pagination.innerHTML = "";
    return;
  }

  let html = `<button type="button" data-page="${projectState.page - 1}" ${projectState.page === 1 ? "disabled" : ""}><i class="bi bi-chevron-left"></i></button>`;
  for (let i = 1; i <= pageCount; i++) {
    html += `<button type="button" class="${i === projectState.page ? "active" : ""}" data-page="${i}">${i}</button>`;
  }
  html += `<button type="button" data-page="${projectState.page + 1}" ${projectState.page === pageCount ? "disabled" : ""}><i class="bi bi-chevron-right"></i></button>`;

  pagination.innerHTML = html;
}

function refreshProjectList() {
  const container = document.getElementById("projectsContainer");
  if (!container) return;

  const filtered = applyFilters(projectState.allProjects);
  const pagedData = getPagedData(filtered);

  if (pagedData.total === 0) {
    container.innerHTML = '<div class="empty-projects">No projects found for the selected filters.</div>';
  } else {
    container.innerHTML = pagedData.paged.map(projectCardTemplate).join("");
  }

  container.classList.toggle("project-grid-mode", projectState.view === "grid");

  const countText = document.getElementById("projectCountText");
  if (countText) {
    countText.textContent = `Showing ${pagedData.start} to ${pagedData.end} of ${pagedData.total} projects`;
  }

  renderPagination(pagedData.pageCount);
}

function renderProjects() {
  fetch("get_projects.php")
    .then(res => res.json())
    .then(projects => {
      projectState.allProjects = Array.isArray(projects) ? projects : [];
      setupLocationFilter(projectState.allProjects);
      refreshProjectList();
    });
}

function bindProjectUI() {
  const searchInput = document.getElementById("projectSearch");
  const statusFilter = document.getElementById("statusFilter");
  const locationFilter = document.getElementById("locationFilter");
  const sortFilter = document.getElementById("sortFilter");
  const gridBtn = document.getElementById("viewGridBtn");
  const listBtn = document.getElementById("viewListBtn");
  const pagination = document.getElementById("projectsPagination");

  if (searchInput) {
    searchInput.addEventListener("input", e => {
      projectState.search = e.target.value || "";
      projectState.page = 1;
      refreshProjectList();
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener("change", e => {
      projectState.status = e.target.value;
      projectState.page = 1;
      refreshProjectList();
    });
  }

  if (locationFilter) {
    locationFilter.addEventListener("change", e => {
      projectState.location = e.target.value;
      projectState.page = 1;
      refreshProjectList();
    });
  }

  if (sortFilter) {
    sortFilter.addEventListener("change", e => {
      projectState.sortBy = e.target.value;
      refreshProjectList();
    });
  }

  if (gridBtn && listBtn) {
    gridBtn.addEventListener("click", () => {
      projectState.view = "grid";
      gridBtn.classList.add("active");
      listBtn.classList.remove("active");
      refreshProjectList();
    });

    listBtn.addEventListener("click", () => {
      projectState.view = "list";
      listBtn.classList.add("active");
      gridBtn.classList.remove("active");
      refreshProjectList();
    });
  }

  if (pagination) {
    pagination.addEventListener("click", event => {
      const btn = event.target.closest("button[data-page]");
      if (!btn || btn.disabled) return;
      const page = parseInt(btn.getAttribute("data-page"), 10);
      if (Number.isNaN(page) || page < 1) return;
      projectState.page = page;
      refreshProjectList();
    });
  }
}

function editProject(id) {
  fetch("get_projects.php")
    .then(res => res.json())
    .then(projects => {
      const p = projects.find(x => String(x.id) === String(id));
      if (!p) return;

      openForm();
      document.getElementById("title").value = p.title || "";
      document.getElementById("city").value = p.city || "";
      document.getElementById("address").value = p.address || "";
      document.getElementById("start").value = p.start_date || "";
      document.getElementById("end").value = p.end_date || "";
      editId = id;
    });
}

function viewProject(id) {
  window.location.href = `project_details.php?id=${encodeURIComponent(id)}`;
}

function saveProject() {
  const title = document.getElementById("title").value.trim();
  const city = document.getElementById("city").value.trim();
  const address = document.getElementById("address").value.trim();
  const start = document.getElementById("start").value;
  const end = document.getElementById("end").value;
  const file = document.getElementById("image").files[0];

  if (!title || !city || !address || !start || !end) {
    return alert("Fill all fields");
  }

  const formData = new FormData();
  formData.append("title", title);
  formData.append("city", city);
  formData.append("address", address);
  formData.append("start", start);
  formData.append("end", end);

  if (file) {
    formData.append("image", file);
  }

  if (editId !== null) {
    formData.append("id", editId);

    fetch("update_project.php", {
      method: "POST",
      body: formData
    })
      .then(res => res.text())
      .then(data => {
        if (data === "success") {
          alert("Project updated");
          editId = null;

          addSystemUpdate(
            "Project Updated",
            `${title} project updated`,
            "PROGRESS"
          );

          closeForm();
          renderProjects();
        } else {
          alert(data);
        }
      });
  } else {
    fetch("add_project.php", {
      method: "POST",
      body: formData
    })
      .then(res => res.text())
      .then(data => {
        let parsed = null;
        try {
          parsed = JSON.parse(data);
        } catch (error) {
          parsed = null;
        }

        const isSuccess = data === "success" || (parsed && parsed.status === "success");

        if (isSuccess) {
          const projectIdText = parsed && parsed.project_id ? `\nProject ID: ${parsed.project_id}` : "";
          alert(`Project added${projectIdText}`);

          addSystemUpdate(
            "Project Created",
            `${title} project added`,
            "PROGRESS"
          );

          closeForm();
          renderProjects();
        } else {
          alert(data);
        }
      });
  }
}

function deleteProject(id) {
  if (!confirm("Delete project?")) return;

  const formData = new FormData();
  formData.append("id", id);

  fetch("delete_project.php", {
    method: "POST",
    body: formData
  })
    .then(() => {
      renderProjects();

      addSystemUpdate(
        "Project Deleted",
        "Project removed",
        "SAFETY"
      );
    });
}

function openForm() {
  document.getElementById("formModal").classList.add("active");
}

function closeForm() {
  document.getElementById("formModal").classList.remove("active");
}

document.addEventListener("DOMContentLoaded", () => {
  bindProjectUI();
  renderProjects();
});