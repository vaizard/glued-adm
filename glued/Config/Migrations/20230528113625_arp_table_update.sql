-- migrate:up

ALTER TABLE t_adm_network_arp
ADD COLUMN c_firstseen TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp: first seen',
ADD COLUMN c_countseen INT NOT NULL DEFAULT 1 COMMENT 'Count seen - autoincremented by a trigger.';

-- Create a BEFORE UPDATE trigger to increment the value
CREATE TRIGGER increment_c_countseen BEFORE UPDATE ON t_adm_network_arp
FOR EACH ROW
BEGIN
    SET NEW.c_countseen = OLD.c_countseen + 1;
END;


-- migrate:down

ALTER TABLE t_adm_network_arp
  DROP COLUMN c_firstseen,
  DROP COLUMN c_countseen;

DROP TRIGGER increment_c_countseen;

