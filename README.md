# JGC Constructions - Construction Management ERP & CRM System

A comprehensive, production-ready enterprise resource planning (ERP) and customer relationship management (CRM) system designed for construction firms. Built with a responsive **PHP/MySQL** backend and a tailored **modern CSS dashboard theme (Gold & Slate)**, this system simplifies site coordination, lead tracking, worker attendance, multi-site material inventory, and financial ledger management.

---

## 🚀 Key Modules & Capabilities

### 1. Unified Executive Dashboard (`dashboard.php`)
- **KPI Metrics Tracker**: Real-time cards displaying total active projects, active sites, accumulated material costs, and pending approvals.
- **Financial Snapshot Widget**: Visually reports total budget, actual spent, remaining budget, and percentage utilization using dynamic CSS progress bars.
- **Project Progress Overview**: Renders progress cards, target dates, and phase badges for a high-level summary.
- **Live Alerts Panel**: Displays high-priority issues, such as low-stock alerts, overdue follow-ups, and urgent material transfer approvals.

### 2. CRM & Sales Workspace (`leads.php`, `leads_list.php`)
- **Lead Tracking & Pipeline**: Monitors customer queries from inception to quotation approval.
- **Activity & Contact Log**: Logs customer communications (Calls, WhatsApp, Meetings, Emails).
- **Auto-Sequence Identifiers**: Uses a sequential database-driven generator to auto-assign formatted codes (`JGC-LD-YYYY-0000X`) to incoming leads.
- **Site Visit Planner**: Schedules engineers for visits, logs status, and records onsite customer feedback.

### 3. Quotation & BOQ (Bill of Quantities) Engine (`quotations.php`, `new_quotation.php`)
- **Interactive BOQ Grid**: Dynamically adds categories (Civil, Plumbing, Electrical) and individual items.
- **Automatic Financial Math**: Computes sub-totals, discounts, GST (18%), and final figures on the fly.
- **Revision Control**: Retains document states (Draft, Sent, Approved, Revised) to audit design modifications.

### 4. Multi-Site Material Inventory ERP (`materials.php`)
- **SKU Management**: Categorizes assets and raw materials (Cement, TMT Steel, Sand) with unit-of-measure tracking.
- **Multi-Site Stock Balances**: Evaluates quantities available across individual projects, warehouses, and storage yards.
- **Transaction Logs**: Registers stock movements (Purchases, Issues to Worksite, Internal Transfers, and Wastage/Damages).
- **Auto-Reorder Safeguards**: Highlights inventory that falls below the designated threshold to prevent work stoppages.

### 5. Income & Expense General Ledger (`income_expense.php`)
- **Project Cash Flows**: Links monetary inflows (Client Payments, Advances) and outflows (Labor Wages, Transport, Materials) to projects.
- **Excel Transaction Import**: Contains file upload logic to parse and ingest bulk accounting records in one click.
- **Report Downloads**: Generates and formats accounting reports for offline reconciliation.

### 6. Workforce & Attendance Log (`workers.php`)
- **Labor Registry**: Stores contact records, specialized skills (Mason, Supervisor, Welder), and hourly rates.
- **Attendance & Shift Management**: Toggles daily active shifts (Day/Night Shifts, Overtime) and tracks payroll obligations.

---

## 🛠️ Technology Stack

| Layer | Technologies & Frameworks |
| :--- | :--- |
| **Frontend** | HTML5, CSS3 (Vanilla CSS + Custom Theme), JavaScript (ES6+ AJAX, Dynamic DOM), Bootstrap 5, Bootstrap Icons |
| **Backend** | PHP (Session Management, Prepared Statements, Secure File Uploads) |
| **Database** | MySQL (Relational Tables, Foreign Key Constraints, Indexed Search, DB Stored Procedures) |
| **Scripts** | Python (Database patching and CSS configuration automation helpers) |

---

## 📂 Project Directory Structure

```filepath
c:\xampp\htdocs\jgc_constructions(7-2-26)
├── css/                             # Custom visual styling & layouts
│   ├── jgc-theme.css                # Enterprise Gold (--primary-gold: #D4AF37) variables and buttons
│   ├── sidebar.css                  # Collapsible multi-level sidebar navigation
│   ├── dashboard.css                # Flexbox and CSS Grid layout rules for cards & KPI tiles
│   └── *.css                        # Page-specific views styling (leads, projects, reports)
├── js/                              # Front-end interactions & AJAX integrations
│   ├── project_details.js           # Dynamic tab switcher and progress computations
│   └── *.js                         # Dynamic state transitions and asynchronous updates
├── img/                             # Asset management (Company logo & icons)
├── sql/                             # Schema definitions and database upgrades
│   ├── materials_erp_schema.sql     # Baseline structure for multi-site warehouse inventory
│   ├── income_expense.sql           # General ledger categorization & transaction records
│   ├── construction_db_patch_v2.sql # Safely implements leads, sites, and missing columns
│   ├── construction_db_patch_v3.sql # Adds CRM Customer Journey tables (activities, site visits)
│   ├── construction_db_patch_v4.sql # Introduces CRM Quotations & BOQ schema
│   └── construction_db_patch_v5.sql # Unique lead code back-fill procedure
├── db.php                           # Establishes MySQLi database connections
├── auth.php                         # Authenticates and restricts access using PHP Sessions
├── dashboard.php                    # System central control panel
├── leads.php & leads_list.php       # Customer Relationship Management views
├── quotations.php & new_quotation.php# Bid preparation tools
├── projects.php & project_details.php# Onsite execution logs and task trackers
├── materials.php                    # Storage depot and site warehouse manager
├── workers.php                      # Personnel database and shift logs
├── income_expense.php               # Double-entry ledger manager
├── reports.php                      # Business intelligence graphs and reports
├── read_login.txt                   # User authorization helper notes
└── README.md                        # Documentation summary (this file)
```

---

## 🗄️ Database Architecture (Entity Overview)

Below is an overview of the core database tables that form the relational backbone of **JGC Constructions**:

- **`projects`**: Tracks site locations, budgets, dates, and completion status.
- **`leads`**: Centralizes prospect details, company affiliation, budgets, status, and custom sequence identifiers (`lead_code`).
- **`lead_activities`**, `lead_followups`, `lead_site_visits`: Supports CRM engagement history and site check-ins.
- **`quotations`** & `quotation_items`: BOQ records maintaining client prices, tax rates (18% standard GST), and discounts.
- **`sites`** & `vendors`: ERP entities for supply chain logistics.
- **`materials`**, `material_stock`, `material_transactions`: Manages global inventory counts, standard costs, and site-level available stock.
- **`income_expense`**: Bookkeeping journal logs mapped to project identifiers.
- **`workers`**: Records profiles, wages, shifts, and assigned workspace sites.

---

## ⚙️ Installation & Setup

To run this application locally on your machine, follow these instructions:

### Prerequisites
- **XAMPP** or **WAMP** server (PHP 7.4+ or PHP 8.x recommended, Apache server, and MySQL database).
- Web browser (Chrome, Firefox, Edge, Safari).

### Setup Instructions

1. **Clone/Download the Files**:
   - Download the code folder and place it in your local server directory:
     - For XAMPP: `C:\xampp\htdocs\jgc_constructions`
     - For WAMP: `C:\wamp\www\jgc_constructions`

2. **Initialize the Database**:
   - Open your browser and navigate to `http://localhost/phpmyadmin/`.
   - Create a new database named `construction_db` with collation `utf8mb4_general_ci`.

3. **Import SQL Schemas**:
   - Run the baseline files and migrations in the following order under the **Import** tab or SQL query window in phpMyAdmin:
     1. Import `sql/materials_erp_schema.sql` (Creates base sites, materials, and inventory structure).
     2. Import `sql/income_expense.sql` (Creates categories and financial ledger tables).
     3. Import `sql/construction_db_patch_v2.sql` (Creates leads, material stock updates, and baseline tables).
     4. Import `sql/construction_db_patch_v3.sql` (Creates CRM follow-up and activity tables).
     5. Import `sql/construction_db_patch_v4.sql` (Creates quotation and itemized BOQ tables).
     6. Import `sql/construction_db_patch_v5.sql` (Executes the sequential migration to add `lead_code`).

4. **Verify Database Configuration**:
   - Verify the database connection properties in [db.php](file:///c:/xampp/htdocs/jgc_constructions(7-2-26)/db.php):
     ```php
     $conn = new mysqli("localhost", "root", "", "construction_db");
     ```
   - Change the password argument if your local MySQL instance does not use the default empty password.

5. **Start Apache and MySQL**:
   - Open the XAMPP / WAMP Control Panel and start both the **Apache** and **MySQL** services.

6. **Access the System**:
   - Navigate to `http://localhost/jgc_constructions/login.html` in your browser.
   - Enter your email and password credentials to log into the Executive Dashboard.

---

## 📈 Key Learnings & Engineering Outcomes
- Designed and implemented a normalized relational database schema with cascades to prevent dangling records.
- Utilized stored procedures in MySQL for safe, schema-preserving migrations (e.g., column addition and sequential auto-naming).
- Developed a custom CSS-driven UI style-guide, relying on flex layouts and grid modules without bloated utility frameworks.
- Created robust multi-site material transaction logs, maintaining standard cost valuation metrics (`avg_cost`, `standard_cost`) dynamically.
- Implemented AJAX hooks to facilitate smooth page interactions and metrics updates.

---

## 🧑‍💻 Developed By
- **Name**: Mohammed Shameem
- **Email**: mohammedshameem1105@gmail.com
- **LinkedIn**: https://www.linkedin.com/in/shameem1105/
- **GitHub**: https://github.com/Shameem1105
