-- JGC Constructions ERP Database Patch v2
-- Purpose: Resolve missing columns, add CRM leads table, and prevent query crashes.

SET NAMES utf8mb4;

-- 1. Create leads table if not exists
CREATE TABLE IF NOT EXISTS leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  company VARCHAR(150) DEFAULT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(100) DEFAULT NULL,
  source VARCHAR(100) DEFAULT 'Other',
  status VARCHAR(50) DEFAULT 'New',
  owner VARCHAR(100) DEFAULT NULL,
  project_type VARCHAR(150) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  followup_date DATE DEFAULT NULL,
  gst_number VARCHAR(50) DEFAULT NULL,
  company_name VARCHAR(150) DEFAULT NULL,
  address TEXT DEFAULT NULL,
  requirement_type VARCHAR(100) DEFAULT NULL,
  budget DECIMAL(12,2) DEFAULT NULL,
  lead_source VARCHAR(100) DEFAULT NULL,
  lead_status VARCHAR(50) DEFAULT NULL,
  assigned_to VARCHAR(100) DEFAULT NULL,
  next_followup_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create sites table if not exists
CREATE TABLE IF NOT EXISTS sites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  site_code VARCHAR(32) DEFAULT NULL,
  site_name VARCHAR(160) NOT NULL,
  site_type VARCHAR(50) DEFAULT 'project',
  status VARCHAR(20) DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create material_stock table if not exists
CREATE TABLE IF NOT EXISTS material_stock (
  id INT AUTO_INCREMENT PRIMARY KEY,
  material_id INT NOT NULL,
  site_id INT NOT NULL,
  current_stock DECIMAL(14,3) DEFAULT 0.000,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create material_transactions table if not exists
CREATE TABLE IF NOT EXISTS material_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  total_amount DECIMAL(16,2) DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Add missing columns safely using a stored procedure to prevent duplicate column errors
DROP PROCEDURE IF EXISTS AddColumnUnlessExists;

DELIMITER //
CREATE PROCEDURE AddColumnUnlessExists(
    IN tbl_name VARCHAR(64),
    IN col_name VARCHAR(64),
    IN col_definition VARCHAR(255)
)
BEGIN
    DECLARE col_count INT;
    SELECT COUNT(*) INTO col_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = tbl_name
      AND column_name = col_name;
      
    IF col_count = 0 THEN
        SET @sql_stmt = CONCAT('ALTER TABLE ', tbl_name, ' ADD COLUMN ', col_name, ' ', col_definition);
        PREPARE stmt FROM @sql_stmt;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL AddColumnUnlessExists('projects', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
CALL AddColumnUnlessExists('workers', 'worker_type', 'VARCHAR(50) DEFAULT NULL');
CALL AddColumnUnlessExists('materials', 'material_name', 'VARCHAR(200) DEFAULT NULL');
CALL AddColumnUnlessExists('materials', 'minimum_stock', 'INT DEFAULT 0');
CALL AddColumnUnlessExists('materials', 'category', 'VARCHAR(120) DEFAULT NULL');
CALL AddColumnUnlessExists('updates', 'update_type', 'VARCHAR(50) DEFAULT NULL');

DROP PROCEDURE IF EXISTS AddColumnUnlessExists;
