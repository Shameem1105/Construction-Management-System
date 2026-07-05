let projects = [];
let materialsDB = [];

let selectedProjectId = null;
let editId = null;

// 🔹 LOAD PROJECTS
function loadProjects() {
  return fetch("get_projects.php")
    .then(res => res.json())
    .then(data => {
      projects = data;
      loadProjectList();
    });
}

// 🔹 LOAD MATERIALS
function loadMaterials() {
  return fetch("get_materials.php")
    .then(res => res.json())
    .then(data => {
      materialsDB = data;
      return materialsDB;
    });
}

// 🔹 OPEN STEP1
function openStep1() {
  document.getElementById("step1").classList.add("active");
}

// 🔹 CLOSE ALL
function closeAll() {
  document.querySelectorAll(".step").forEach(s => s.classList.remove("active"));
  clearInputs();
}

// 🔹 LOAD PROJECT LIST
function loadProjectList() {
  const list = document.getElementById("projectList");
  if (!list) return;
  list.innerHTML = "";

  projects.forEach(p => {
    list.innerHTML += `
      <div onclick="selectProject(${p.id})"
        style="padding:10px;border:1px solid #ddd;margin:5px;cursor:pointer;">
        ${p.title} (${p.city})
      </div>
    `;
  });
}

// 🔹 SELECT PROJECT
function selectProject(id) {
  selectedProjectId = id;
  closeAll();
  document.getElementById("step2").classList.add("active");
}

// 🔹 ADD MATERIAL
function addMaterial() {
  const name = document.getElementById("materialName").value.trim();
  const qty = document.getElementById("quantity").value;
  const unit = document.getElementById("unit").value;

  if (!name || !qty || !unit) {
    return alert("Fill all fields");
  }

  const formData = new FormData();
  formData.append("project_id", selectedProjectId);
  formData.append("name", name);
  formData.append("quantity", qty);
  formData.append("unit", unit);

  fetch("add_material.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(() => {
    if (typeof addSystemUpdate === 'function') {
      addSystemUpdate(
        "Material Added",
        `${name} added (${qty} ${unit})`,
        "DELIVERY"
      );
    }

    clearInputs();
    loadMaterials().then(renderDashboard);
  });
}

// 🔹 RENDER DASHBOARD
function renderDashboard() {
  // totals
  const totalItems = materialsDB.reduce((s, m) => s + Number(m.quantity || 0), 0);
  const stockValue = totalItems * 1200; // simple estimated multiplier for visual

  document.getElementById('totalItems').innerText = totalItems;
  document.getElementById('totalStockValue').innerText = '₹' + numberWithCommas(stockValue);

  // low stock
  const low = materialsDB.filter(m => Number(m.quantity) < 20);
  document.getElementById('lowStockCount').innerText = low.length;

  renderStockTable(materialsDB);
  renderRecentTransactions();
  renderChart(materialsDB);
  renderLowAlerts(low);
}

function numberWithCommas(x) {
  return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// 🔹 RENDER STOCK TABLE
function renderStockTable(list) {
  const tbody = document.querySelector('#stockSummaryTable tbody');
  tbody.innerHTML = '';

  list.forEach(m => {
    const qty = Number(m.quantity || 0);
    const stockVal = qty * 1200; // visual estimate
    const status = qty < 20 ? '<span class="badge bg-danger">Low</span>' : '<span class="badge bg-success">Good</span>';

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${escapeHtml(m.name)}</td>
      <td>${escapeHtml(m.unit)}</td>
      <td>—</td>
      <td>—</td>
      <td>${qty}</td>
      <td>${qty}</td>
      <td>₹${numberWithCommas(stockVal)}</td>
      <td>${status}</td>
    `;
    tbody.appendChild(tr);
  });
}

function escapeHtml(unsafe) {
  return (unsafe + '').replace(/[&<"'>]/g, function(m) { return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]; });
}

// 🔹 RECENT TRANSACTIONS
function renderRecentTransactions() {
  fetch('get_updates.php')
    .then(res => res.json())
    .then(data => {
      const wrap = document.getElementById('recentTransactions');
      wrap.innerHTML = '';
      data.slice(0,8).forEach(d => {
        const div = document.createElement('div');
        div.className = 'trx-row';
        const date = d.created_at ? d.created_at.split(' ')[0] : '';
        div.innerHTML = `<div>${escapeHtml(d.type||d.update_type||'Update')} — ${escapeHtml(d.material||d.details||'')}</div><div>${date}</div>`;
        wrap.appendChild(div);
      });
    }).catch(err => { console.warn(err); });
}

// 🔹 CHART
function renderChart(list) {
  // categorize by common names
  const groups = { Cement:0, Steel:0, Sand:0, Bricks:0, Others:0 };
  list.forEach(m => {
    const name = (m.name||'').toLowerCase();
    const q = Number(m.quantity||0);
    if (name.includes('cement')) groups.Cement += q;
    else if (name.includes('steel')) groups.Steel += q;
    else if (name.includes('sand')) groups.Sand += q;
    else if (name.includes('brick')) groups.Bricks += q;
    else groups.Others += q;
  });

  const labels = Object.keys(groups);
  const values = labels.map(l => groups[l]);

  // load Chart.js if not present
  if (typeof Chart === 'undefined') {
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    s.onload = () => drawChart(labels, values);
    document.head.appendChild(s);
  } else drawChart(labels, values);
}

function drawChart(labels, values) {
  const ctx = document.getElementById('stockValueChart').getContext('2d');
  if (window._materialsChart) window._materialsChart.destroy();
  window._materialsChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{ data: values, backgroundColor: ['#4e73df','#1cc88a','#36b9cc','#f6c23e','#e74a3b'] }]
    },
    options: { plugins: { legend: { display: false } }, maintainAspectRatio: false }
  });

  const legend = document.getElementById('chartLegend');
  legend.innerHTML = '';
  labels.forEach((l, i) => {
    const item = document.createElement('div'); item.className = 'item';
    item.innerHTML = `<div class="swatch" style="background:${window._materialsChart.data.datasets[0].backgroundColor[i]}"></div><div>${l}</div>`;
    legend.appendChild(item);
  });
}

function renderLowAlerts(list) {
  const wrap = document.getElementById('lowStockAlerts');
  if (!wrap) return;
  if (!list || list.length === 0) { wrap.innerHTML = '<div>No low stock items</div>'; return; }
  wrap.innerHTML = '';
  list.forEach(m => {
    const d = document.createElement('div');
    d.innerHTML = `<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f1f1;"><div>${escapeHtml(m.name)}</div><div><small>Balance: ${m.quantity}</small> <span class="badge bg-danger">Low</span></div></div>`;
    wrap.appendChild(d);
  });
}

// 🔹 DELETE
function deleteMaterial(id) {
  if (!confirm("Delete?")) return;

  const formData = new FormData();
  formData.append("id", id);

  fetch("delete_material.php", {
    method: "POST",
    body: formData
  })
  .then(() => {
    loadMaterials().then(renderDashboard);

    if (typeof addSystemUpdate === 'function') {
      addSystemUpdate(
        "Material Deleted",
        "Material removed",
        "DELIVERY"
      );
    }
  });
}

// 🔹 EDIT
function editMaterial(id) {
  const m = materialsDB.find(x => x.id == id);
  if (!m) return;

  document.getElementById("materialName").value = m.name;
  document.getElementById("quantity").value = m.quantity;
  document.getElementById("unit").value = m.unit;

  editId = id;

  closeDetails();
  document.getElementById("step2").classList.add("active");
}

// 🔹 UPDATE (on Done click)
function finishMaterials() {
  if (editId === null) return closeAll();

  const formData = new FormData();
  formData.append("id", editId);
  formData.append("name", document.getElementById("materialName").value);
  formData.append("quantity", document.getElementById("quantity").value);
  formData.append("unit", document.getElementById("unit").value);

  fetch("update_material.php", {
    method: "POST",
    body: formData
  })
  .then(() => {
    if (typeof addSystemUpdate === 'function') {
      addSystemUpdate(
        "Material Updated",
        "Material updated",
        "DELIVERY"
      );
    }

    editId = null;
    closeAll();
    loadMaterials().then(renderDashboard);
  });
}

// 🔹 CLEAR
function clearInputs() {
  document.getElementById("materialName").value = "";
  document.getElementById("quantity").value = "";
  document.getElementById("unit").value = "";
}

// 🔹 CLOSE DETAILS
function closeDetails() {
  document.getElementById("detailsModal").classList.remove("active");
}

// 🔹 INIT
document.addEventListener("DOMContentLoaded", function(){
  loadProjects().then(()=>{
    loadMaterials().then(renderDashboard);
  });
});