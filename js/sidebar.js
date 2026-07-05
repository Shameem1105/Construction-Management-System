
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  if (sidebar) {
    sidebar.classList.toggle("active");
  }
}

document.addEventListener("click", function (event) {
  const sidebar = document.getElementById("sidebar");
  const hamburger = document.querySelector(".hamburger");

  if (!sidebar || !hamburger) return;

  if (
    sidebar.classList.contains("active") &&
    !sidebar.contains(event.target) &&
    !hamburger.contains(event.target)
  ) {
    sidebar.classList.remove("active");
  }
});

document.addEventListener("DOMContentLoaded", function () {
  // Collapsible submenu toggle behavior
  const toggles = document.querySelectorAll(".submenu-toggle");
  toggles.forEach(function (toggle) {
    toggle.addEventListener("click", function (e) {
      e.preventDefault(); // Parent link acts purely as a folder toggle
      const parent = this.closest(".has-submenu");
      if (parent) {
        parent.classList.toggle("open");
      }
    });
  });

  // Ensure active parent is open on load
  const activeParent = document.querySelector('.has-submenu.active');
  if (activeParent) {
    activeParent.classList.add('open');
  }
});
