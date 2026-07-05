// ============================
// 🔹 DATE ONLY
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
// 🔹 LIVE DATE + TIME
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

// ============================
// 🔹 INIT ON LOAD
// ============================
document.addEventListener("DOMContentLoaded", function () {

  // 👉 Choose ONE:
  // updateDate();          // Static date
  updateDateTime();        // Live date + time

  // Update every second (only for live mode)
  setInterval(updateDateTime, 1000);

});