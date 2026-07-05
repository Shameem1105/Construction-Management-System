CREATE TABLE IF NOT EXISTS income_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  type ENUM('income') NOT NULL DEFAULT 'income',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expense_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  type ENUM('expense') NOT NULL DEFAULT 'expense',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS income_expense (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  type ENUM('income','expense') NOT NULL,
  category VARCHAR(120) NOT NULL,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(60) DEFAULT NULL,
  party_name VARCHAR(150) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  receipt VARCHAR(255) DEFAULT NULL,
  entry_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_income_expense_project (project_id),
  INDEX idx_income_expense_type (type),
  INDEX idx_income_expense_date (entry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO income_categories (name, type) VALUES
('Client Payment', 'income'),
('Advance Received', 'income'),
('Material Return', 'income'),
('Other Income', 'income');

INSERT IGNORE INTO expense_categories (name, type) VALUES
('Labour', 'expense'),
('Material', 'expense'),
('Transport', 'expense'),
('Equipment', 'expense'),
('Site Expense', 'expense'),
('Others', 'expense');
