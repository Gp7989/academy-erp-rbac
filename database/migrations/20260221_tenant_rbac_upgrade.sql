-- 1) Add tenant ownership to users
ALTER TABLE users ADD COLUMN owner_id INT NULL AFTER id;
ALTER TABLE users ADD CONSTRAINT fk_users_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE;

-- Existing root users become tenant owners
UPDATE users SET owner_id = NULL WHERE owner_id IS NULL;

-- 2) Add tenant ownership to roles
ALTER TABLE roles ADD COLUMN owner_id INT NULL AFTER id;

-- Backfill existing roles to each existing owner (legacy roles duplicated per owner)
INSERT INTO roles (owner_id, role_name)
SELECT u.id, 'Owner'
FROM users u
WHERE u.owner_id IS NULL
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

ALTER TABLE roles MODIFY owner_id INT NOT NULL;
ALTER TABLE roles DROP INDEX role_name;
ALTER TABLE roles ADD UNIQUE KEY uk_roles_owner_name (owner_id, role_name);
ALTER TABLE roles ADD CONSTRAINT fk_roles_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE;

-- 3) Add owner_id to admissions and backfill from submitter owner chain
ALTER TABLE admissions ADD COLUMN owner_id INT NULL AFTER user_id;
UPDATE admissions a
JOIN users u ON u.id = a.user_id
SET a.owner_id = IFNULL(u.owner_id, u.id)
WHERE a.owner_id IS NULL;
ALTER TABLE admissions MODIFY owner_id INT NOT NULL;
ALTER TABLE admissions ADD KEY idx_admissions_owner_created (owner_id, created_at);
ALTER TABLE admissions ADD CONSTRAINT fk_admissions_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE;

-- 4) Migrate permission names to resource-level actions
UPDATE permissions SET permission_name = 'create_form' WHERE permission_name = 'create';
UPDATE permissions SET permission_name = 'edit_form' WHERE permission_name = 'edit';
UPDATE permissions SET permission_name = 'view_form' WHERE permission_name = 'view';
UPDATE permissions SET permission_name = 'delete_form' WHERE permission_name = 'delete';
