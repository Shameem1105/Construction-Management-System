// 🔥 GLOBAL UPDATE FUNCTION
function addSystemUpdate(title, desc, category = "PROGRESS") {

  const formData = new FormData();
  formData.append("title", title);
  formData.append("desc", desc);
  formData.append("category", category);

  fetch("add_update.php", {
    method: "POST",
    body: formData
  })
  .then(() => {
    loadUpdates(); // 🔥 INSTANT REFRESH
  });
}

// 🔹 LOAD UPDATES
function loadUpdates() {
  fetch("get_updates.php")
    .then(res => res.json())
    .then(data => {
      renderUpdates(data);
    })
    .catch(err => console.log("Error:", err));
}

// 🔹 RENDER
function renderUpdates(updates) {
  const container = document.getElementById("updatesContainer");

  if (!container) return;

  container.innerHTML = "";

  updates.forEach(u => {
    container.innerHTML += `
      <div class="update-card">
        <h6>${u.title}</h6>
        <div class="meta">${u.created_at}</div>
        <p>${u.description}</p>
        <span class="tag ${u.category}">${u.category}</span>
      </div>
    `;
  });
}

// 🔹 INIT
document.addEventListener("DOMContentLoaded", () => {
  loadUpdates();

  setInterval(() => {
    loadUpdates();
  }, 3000);
});