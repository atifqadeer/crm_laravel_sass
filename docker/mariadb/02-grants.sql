-- ─────────────────────────────────────────────────────────────────────────────
-- Grant ALL PRIVILEGES to crm_user on crm_db_portal.
--
-- This file runs automatically after 01-crm-init.sql (alphabetical order)
-- when the db_data volume is empty (first container start).
--
-- crm_user is already created by MariaDB via MYSQL_USER / MYSQL_PASSWORD,
-- but only gets basic privileges on the named database.  Granting ALL here
-- allows the user to CREATE/DROP tables (needed for migrations) and manage
-- stored procedures, triggers, etc.
-- ─────────────────────────────────────────────────────────────────────────────

GRANT ALL PRIVILEGES ON `crm_db_portal`.* TO 'crm_user'@'%';
FLUSH PRIVILEGES;
