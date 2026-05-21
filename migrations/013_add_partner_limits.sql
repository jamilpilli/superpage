ALTER TABLE users
    ADD COLUMN partner_max_clients INT NULL DEFAULT NULL AFTER partner_max_sites,
    ADD COLUMN partner_max_sites   INT NULL DEFAULT NULL AFTER status;
-- NULL = sem limite
