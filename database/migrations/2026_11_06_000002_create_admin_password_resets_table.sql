-- Administrator Password Resets Table
CREATE TABLE IF NOT EXISTS admin_password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_admin_resets_token ON admin_password_resets(token);
CREATE INDEX IF NOT EXISTS idx_admin_resets_email ON admin_password_resets(email);

