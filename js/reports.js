let reports = [];

// 🔹 LOAD REPORTS
function loadReports() {
  fetch("get_reports.php")
    .then(res => res.json())
    .then(data => {
      reports = data;
      renderReports();
    });
}

// 🔹 RENDER
function renderReports() {
  const container = document.getElementById("reportsContainer");
  container.innerHTML = "";

  reports.forEach(r => {
    container.innerHTML += `
      <div class="col-md-4">
        <div class="report-card" onclick="openDetails(${r.id})">
          <h6>${r.title}</h6>
          <h2>₹${Number(r.expense).toLocaleString()}</h2>
          <p>Out of ₹${Number(r.budget).toLocaleString()}</p>
          <p class="green">${r.progress}% Progress</p>
        </div>
      </div>
    `;
  });
}

// 🔹 DETAILS
function openDetails(projectId) {
  const r = reports.find(x => x.id == projectId);

  document.getElementById("reportDetails").classList.add("active");
  document.getElementById("reportTitle").innerText = r.title;

  document.getElementById("reportContent").innerHTML = `
    <p><b>Total Expenses:</b> ₹${r.expense}</p>
    <p><b>Budget:</b> ₹${r.budget}</p>
    <p><b>Progress:</b> ${r.progress}%</p>
    <p><b>Days Remaining:</b> ${r.days}</p>

    <hr>
  `;
}

// 🔹 CLOSE DETAILS
function closeDetails() {
  document.getElementById("reportDetails").classList.remove("active");
}

// 🔹 OPEN UPDATE
function openUpdate() {
  const modal = document.getElementById("updateModal");
  const select = document.getElementById("reportProject");

  select.innerHTML = "";

  reports.forEach(r => {
    select.innerHTML += `<option value="${r.id}">${r.title}</option>`;
  });

  modal.classList.add("active");
}

// 🔹 CLOSE UPDATE
function closeUpdate() {
  document.getElementById("updateModal").classList.remove("active");
}

// 🔹 SAVE REPORT
function saveReport() {
  const project_id = document.getElementById("reportProject").value;

  const formData = new FormData();
  formData.append("project_id", project_id);
  formData.append("expense", document.getElementById("reportExpense").value);
  formData.append("budget", document.getElementById("reportBudget").value);
  formData.append("progress", document.getElementById("reportProgress").value);
  formData.append("days", document.getElementById("reportDays").value);

  fetch("update_report.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(() => {

  const progress = document.getElementById("reportProgress").value;

  addSystemUpdate(
    "Report Updated",
    `Project progress changed to ${progress}%`,
    "PROGRESS"
  );

  closeUpdate();
  loadReports();
});
}

// 🔹 INIT
document.addEventListener("DOMContentLoaded", loadReports);