<?php
$currentPage = basename($_SERVER['PHP_SELF']);

$navMap = [
  'dashboard' => ['dashboard.php'],
  'projects' => ['projects.php', 'project_details.php', 'add_project.php', 'update_project.php', 'delete_project.php', 'get_project.php', 'get_projects.php'],
  'workers' => ['workers.php', 'add_worker.php', 'delete_worker.php', 'get_workers.php', 'toggle_shift.php'],
  'materials' => ['materials.php', 'add_material.php', 'update_material.php', 'delete_material.php', 'get_materials.php'],
  'income_expense' => ['income_expense.php'],
  'daily' => ['daily.php', 'add_update.php', 'get_updates.php'],
  'reports' => ['reports.php', 'get_reports.php', 'update_report.php'],
  'crm_parent' => ['leads.php', 'leads_list.php', 'add_lead.php'],
  'crm_dashboard' => ['leads.php'],
  'leads' => ['leads_list.php', 'add_lead.php'],
  'quotation_parent' => ['quotations.php', 'new_quotation.php'],
  'quotation_dashboard' => ['quotations.php'],
  'add_quotation' => ['new_quotation.php']
];

$activeKey = '';
foreach ($navMap as $key => $pages) {
  if (in_array($currentPage, $pages, true)) {
    $activeKey = $key;
  }
}

// Special handling to keep parents active if a child is active
$isCrmActive = in_array($currentPage, $navMap['crm_parent']);
$isQuotationActive = in_array($currentPage, $navMap['quotation_parent']);

function sidebar_active($key, $activeKey) {
  return $key === $activeKey ? ' class="active"' : '';
}
?>

<aside class="sidebar" id="sidebar">
  <div class="logo">
    <img src="img/logo.png" alt="JGC Constructions">
  </div>
  <ul class="menu">
    <li<?php echo sidebar_active('dashboard', $activeKey); ?>>
      <a href="dashboard.php"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
    </li>
    
    <li class="has-submenu<?php echo $isCrmActive ? ' active open' : ''; ?>">
      <a href="#" class="submenu-toggle">
        <i class="bi bi-person-lines-fill"></i>
        <span>CRM &amp; Sales</span>
        <i class="bi bi-chevron-down arrow-icon"></i>
      </a>
      <ul class="submenu">
        <li<?php echo sidebar_active('crm_dashboard', $activeKey); ?>><a href="leads.php">Dashboard</a></li>
        <li<?php echo sidebar_active('leads', $activeKey); ?>><a href="leads_list.php">Leads</a></li>
       </ul>
    </li>

    <li class="has-submenu<?php echo $isQuotationActive ? ' active open' : ''; ?>">
      <a href="#" class="submenu-toggle">
        <i class="bi bi-file-earmark-spreadsheet"></i>
        <span>Quotation</span>
        <i class="bi bi-chevron-down arrow-icon"></i>
      </a>
      <ul class="submenu">
        <li<?php echo sidebar_active('quotation_dashboard', $activeKey); ?>><a href="quotations.php">Dashboard</a></li>
        <li<?php echo sidebar_active('add_quotation', $activeKey); ?>><a href="new_quotation.php">Add Quotation</a></li>
      </ul>
    </li>

    <li<?php echo sidebar_active('projects', $activeKey); ?>><a href="projects.php"><i class="bi bi-grid"></i> <span>Projects</span></a></li>
    <li<?php echo sidebar_active('workers', $activeKey); ?>><a href="workers.php"><i class="bi bi-people"></i> <span>Workers</span></a></li>
    <li<?php echo sidebar_active('materials', $activeKey); ?>><a href="materials.php"><i class="bi bi-box"></i> <span>Materials</span></a></li>
    <li<?php echo sidebar_active('income_expense', $activeKey); ?>><a href="income_expense.php"><i class="bi bi-cash-stack"></i> <span>Income &amp; Expense</span></a></li>
    <li<?php echo sidebar_active('daily', $activeKey); ?>><a href="daily.php"><i class="bi bi-journal"></i> <span>Daily Updates</span></a></li>
    <li<?php echo sidebar_active('reports', $activeKey); ?>><a href="reports.php"><i class="bi bi-bar-chart"></i> <span>Reports</span></a></li>
  </ul>
</aside>
