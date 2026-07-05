-- Construction ERP Materials Management schema (MySQL 8.0+)
-- Focus: inventory, multi-site tracking, transactions, vendors, analytics

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- Required tables only: materials, material_stock, material_transactions, sites, vendors

CREATE TABLE IF NOT EXISTS sites (
  site_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  site_code VARCHAR(32) NOT NULL,
  site_name VARCHAR(160) NOT NULL,
  site_type ENUM('project','warehouse','yard','office') NOT NULL DEFAULT 'project',
  address_line1 VARCHAR(200) NULL,
  address_line2 VARCHAR(200) NULL,
  city VARCHAR(80) NULL,
  state VARCHAR(80) NULL,
  postal_code VARCHAR(20) NULL,
  country VARCHAR(80) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (site_id),
  UNIQUE KEY uk_sites_code (site_code),
  KEY idx_sites_type_status (site_type, status),
  KEY idx_sites_name (site_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vendors (
  vendor_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  vendor_code VARCHAR(32) NOT NULL,
  vendor_name VARCHAR(160) NOT NULL,
  contact_person VARCHAR(120) NULL,
  phone VARCHAR(40) NULL,
  email VARCHAR(120) NULL,
  tax_id VARCHAR(64) NULL,
  payment_terms_days INT UNSIGNED NOT NULL DEFAULT 30,
  credit_limit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  address_line1 VARCHAR(200) NULL,
  address_line2 VARCHAR(200) NULL,
  city VARCHAR(80) NULL,
  state VARCHAR(80) NULL,
  postal_code VARCHAR(20) NULL,
  country VARCHAR(80) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (vendor_id),
  UNIQUE KEY uk_vendors_code (vendor_code),
  KEY idx_vendors_name (vendor_name),
  KEY idx_vendors_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS materials (
  material_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sku VARCHAR(40) NOT NULL,
  material_name VARCHAR(200) NOT NULL,
  category VARCHAR(120) NOT NULL,
  uom ENUM('kg','bag','pcs','m','m2','m3','l','ton','unit') NOT NULL,
  material_type ENUM('raw','consumable','tool','asset') NOT NULL DEFAULT 'raw',
  standard_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  last_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  avg_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (material_id),
  UNIQUE KEY uk_materials_sku (sku),
  KEY idx_materials_name (material_name),
  KEY idx_materials_category (category),
  KEY idx_materials_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS material_stock (
  stock_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  material_id BIGINT UNSIGNED NOT NULL,
  site_id BIGINT UNSIGNED NOT NULL,
  on_hand_qty DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  reserved_qty DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  available_qty DECIMAL(14,3) AS (on_hand_qty - reserved_qty) STORED,
  min_qty DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  max_qty DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  reorder_qty DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  avg_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  last_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  stock_value DECIMAL(16,2) AS (on_hand_qty * avg_cost) STORED,
  last_movement_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (stock_id),
  UNIQUE KEY uk_stock_material_site (material_id, site_id),
  KEY idx_stock_site (site_id),
  KEY idx_stock_material (material_id),
  KEY idx_stock_low (site_id, available_qty, min_qty),
  CONSTRAINT fk_stock_materials
    FOREIGN KEY (material_id) REFERENCES materials(material_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_stock_sites
    FOREIGN KEY (site_id) REFERENCES sites(site_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS material_transactions (
  txn_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  material_id BIGINT UNSIGNED NOT NULL,
  site_id BIGINT UNSIGNED NOT NULL,
  txn_type ENUM('opening','purchase','issue','return','transfer_out','transfer_in','adjustment','consumption','damaged','wastage') NOT NULL,
  direction ENUM('in','out') NOT NULL,
  qty DECIMAL(14,3) NOT NULL,
  unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  total_cost DECIMAL(16,2) AS (qty * unit_cost) STORED,
  vendor_id BIGINT UNSIGNED NULL,
  reference_no VARCHAR(60) NULL,
  batch_no VARCHAR(60) NULL,
  from_site_id BIGINT UNSIGNED NULL,
  to_site_id BIGINT UNSIGNED NULL,
  performed_by VARCHAR(120) NULL,
  notes VARCHAR(500) NULL,
  txn_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (txn_id),
  KEY idx_txn_material_date (material_id, txn_date),
  KEY idx_txn_site_date (site_id, txn_date),
  KEY idx_txn_type_date (txn_type, txn_date),
  KEY idx_txn_vendor (vendor_id),
  KEY idx_txn_transfer (from_site_id, to_site_id, txn_date),
  CONSTRAINT fk_txn_materials
    FOREIGN KEY (material_id) REFERENCES materials(material_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_txn_sites
    FOREIGN KEY (site_id) REFERENCES sites(site_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_txn_vendor
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_txn_from_site
    FOREIGN KEY (from_site_id) REFERENCES sites(site_id)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_txn_to_site
    FOREIGN KEY (to_site_id) REFERENCES sites(site_id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data
INSERT INTO sites (site_code, site_name, site_type, city, state, status)
VALUES
  ('SITE-HQ', 'Head Office Store', 'warehouse', 'Chennai', 'TN', 'active'),
  ('PRJ-001', 'Project Alpha', 'project', 'Chennai', 'TN', 'active'),
  ('PRJ-002', 'Project Beta', 'project', 'Bengaluru', 'KA', 'active');

INSERT INTO vendors (vendor_code, vendor_name, contact_person, phone, email, payment_terms_days, credit_limit, status, city, state)
VALUES
  ('VND-STEEL', 'Delta Steel Suppliers', 'R. Kumar', '9876543210', 'sales@deltasteel.example', 30, 250000.00, 'active', 'Chennai', 'TN'),
  ('VND-CEM', 'CemPro Distributors', 'A. Mehta', '9123456780', 'orders@cempro.example', 45, 180000.00, 'active', 'Bengaluru', 'KA');

INSERT INTO materials (sku, material_name, category, uom, material_type, standard_cost, last_cost, avg_cost, status)
VALUES
  ('MAT-CEM-OPC', 'Cement OPC 53 Grade', 'Cement', 'bag', 'raw', 360.00, 365.00, 362.50, 'active'),
  ('MAT-STEEL-12', 'TMT Steel 12mm', 'Steel', 'kg', 'raw', 68.00, 70.00, 69.25, 'active'),
  ('MAT-SAND-RIV', 'River Sand', 'Aggregate', 'm3', 'raw', 1100.00, 1120.00, 1110.00, 'active');

INSERT INTO material_stock (material_id, site_id, on_hand_qty, reserved_qty, min_qty, max_qty, reorder_qty, avg_cost, last_cost, last_movement_at)
VALUES
  (1, 1, 500.000, 40.000, 100.000, 1200.000, 300.000, 362.50, 365.00, '2026-05-15 10:30:00'),
  (2, 1, 2500.000, 150.000, 800.000, 6000.000, 1500.000, 69.25, 70.00, '2026-05-15 09:10:00'),
  (3, 1, 120.000, 10.000, 50.000, 400.000, 120.000, 1110.00, 1120.00, '2026-05-14 17:45:00'),
  (1, 2, 180.000, 20.000, 80.000, 600.000, 200.000, 362.50, 365.00, '2026-05-16 11:00:00');

INSERT INTO material_transactions (material_id, site_id, txn_type, direction, qty, unit_cost, vendor_id, reference_no, batch_no, performed_by, notes, txn_date)
VALUES
  (1, 1, 'purchase', 'in', 300.000, 365.00, 2, 'PO-2026-0510', 'BATCH-CEM-0510', 'stores@hq', 'Cement receipt', '2026-05-10 09:30:00'),
  (2, 1, 'purchase', 'in', 1200.000, 70.00, 1, 'PO-2026-0511', 'BATCH-STEEL-0511', 'stores@hq', 'Steel receipt', '2026-05-11 14:00:00'),
  (1, 2, 'issue', 'out', 120.000, 362.50, NULL, 'REQ-PRJ-001-053', NULL, 'site@prj1', 'Issued to slab work', '2026-05-16 11:00:00'),
  (1, 1, 'transfer_out', 'out', 120.000, 362.50, NULL, 'TRF-PRJ-001-053', NULL, 'stores@hq', 'Transfer to Project Alpha', '2026-05-16 10:45:00'),
  (1, 2, 'transfer_in', 'in', 120.000, 362.50, NULL, 'TRF-PRJ-001-053', NULL, 'site@prj1', 'Transfer from HQ store', '2026-05-16 11:00:00');
