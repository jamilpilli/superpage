ALTER TABLE users
    ADD COLUMN country VARCHAR(2)   NULL AFTER role,
    ADD COLUMN phone   VARCHAR(30)  NULL AFTER country;
