-- JGC Constructions ERP Database Patch v3
-- Purpose: Add CRM Customer Journey workspace tables: lead_activities, lead_followups, lead_site_visits

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS lead_activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lead_id INT NOT NULL,
  activity_type VARCHAR(50) NOT NULL,
  description TEXT NOT NULL,
  user_name VARCHAR(100) DEFAULT 'System',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lead_followups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lead_id INT NOT NULL,
  type VARCHAR(50) NOT NULL, -- Call, Meeting, WhatsApp, Email, Reminder, Site Visit
  notes TEXT DEFAULT NULL,
  followup_date DATE DEFAULT NULL,
  followup_time TIME DEFAULT NULL,
  next_followup_date DATE DEFAULT NULL,
  outcome VARCHAR(200) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lead_site_visits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lead_id INT NOT NULL,
  visit_date DATE NOT NULL,
  engineer VARCHAR(100) NOT NULL,
  site_address TEXT DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'Scheduled', -- Scheduled, Completed, Cancelled
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
