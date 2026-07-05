<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daily Updates</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="css/sidebar.css?v=6">
<link rel="stylesheet" href="css/jgc-theme.css?v=6">
<link rel="stylesheet" href="css/daily.css?v=6">
</head>

<body>

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Main -->
<div class="main">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger d-lg-none" onclick="toggleSidebar()" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <h1 class="page-title">Daily Updates</h1>
        <p class="page-sub">Auto-generated site activity logs</p>
      </div>
    </div>

    <div class="topbar-right">
      <div id="currentDate" class="live-date"></div>
    </div>
  </header>

  <!-- 🔥 UPDATES -->
  <div class="updates" id="updatesContainer"></div>

</div>

<script src="js/sidebar.js"></script>
<script src="js/script1.js"></script>
<script src="js/script.js?v=4"></script>

</body>
</html>