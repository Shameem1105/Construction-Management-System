-- Database Migration: construction_db_patch_v5.sql
-- Purpose: Add lead_code column to leads, populate existing, and enforce uniqueness.

DELIMITER //

CREATE PROCEDURE AddLeadCodeColumnUnlessExists()
BEGIN
    DECLARE col_exists INT DEFAULT 0;
    
    -- Check if column exists
    SELECT COUNT(*) INTO col_exists 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'leads' 
      AND COLUMN_NAME = 'lead_code';
      
    IF col_exists = 0 THEN
        -- Add column
        ALTER TABLE leads ADD COLUMN lead_code VARCHAR(50) DEFAULT NULL;
        
        -- Backfill existing records sequentially by year
        BEGIN
            DECLARE done INT DEFAULT FALSE;
            DECLARE lead_id INT;
            DECLARE lead_created TIMESTAMP;
            DECLARE cur_year INT;
            DECLARE prev_year INT DEFAULT 0;
            DECLARE seq_num INT DEFAULT 0;
            
            DECLARE cur CURSOR FOR SELECT id, created_at FROM leads ORDER BY YEAR(created_at), created_at, id;
            DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
            
            OPEN cur;
            
            read_loop: LOOP
                FETCH cur INTO lead_id, lead_created;
                IF done THEN
                    LEAVE read_loop;
                END IF;
                
                SET cur_year = YEAR(lead_created);
                IF cur_year != prev_year THEN
                    SET prev_year = cur_year;
                    SET seq_num = 1;
                ELSE
                    SET seq_num = seq_num + 1;
                END IF;
                
                UPDATE leads 
                SET lead_code = CONCAT('JGC-LD-', cur_year, '-', LPAD(seq_num, 5, '0')) 
                WHERE id = lead_id;
            END LOOP;
            
            CLOSE cur;
        END;
        
        -- Create unique index
        ALTER TABLE leads ADD UNIQUE INDEX idx_lead_code (lead_code);
    END IF;
END //

DELIMITER ;

CALL AddLeadCodeColumnUnlessExists();
DROP PROCEDURE AddLeadCodeColumnUnlessExists;
