-- JGC Constructions ERP Database Patch v4
-- Purpose: Add CRM Quotation & BOQ Workspace tables: quotations, quotation_items

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS quotations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lead_id INT DEFAULT NULL,
  project_id INT DEFAULT NULL,
  quotation_number VARCHAR(50) NOT NULL,
  version INT NOT NULL DEFAULT 1,
  title VARCHAR(150) NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'Draft', -- Draft, Sent, Approved, Rejected, Revised
  client_name VARCHAR(150) NOT NULL,
  client_company VARCHAR(150) DEFAULT NULL,
  client_address TEXT DEFAULT NULL,
  subtotal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  discount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  grand_total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  notes TEXT DEFAULT NULL,
  created_by VARCHAR(100) NOT NULL DEFAULT 'System',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quotation_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  quotation_id INT NOT NULL,
  category VARCHAR(100) NOT NULL, -- Civil Work, Electrical, Plumbing, Finishing, etc.
  description TEXT NOT NULL,
  unit VARCHAR(20) NOT NULL, -- SqFt, Cum, Rft, Nos, etc.
  quantity DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Indexes for performance
CREATE INDEX idx_qtn_num ON quotations(quotation_number);
CREATE INDEX idx_qtn_status ON quotations(status);
CREATE INDEX idx_qtn_lead ON quotations(lead_id);
