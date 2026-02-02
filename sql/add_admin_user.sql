-- Create an admin user for Loyola Lost & Found.
-- Default credentials: admin@example.com / password (CHANGE PASSWORD after first login).
-- Run once after schema.sql.

USE loyola_lost_and_found;

INSERT INTO users (name, email, role, password_hash)
VALUES (
    'Admin',
    'admin@example.com',
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
)
ON DUPLICATE KEY UPDATE role = 'admin';
