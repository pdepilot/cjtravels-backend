-- Administrators Table
CREATE TABLE IF NOT EXISTS administrators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(100) UNIQUE NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'Administrator',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    remember_token VARCHAR(100) NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_administrators_email ON administrators(email);
CREATE INDEX IF NOT EXISTS idx_administrators_username ON administrators(username);
CREATE INDEX IF NOT EXISTS idx_administrators_role ON administrators(role);
CREATE INDEX IF NOT EXISTS idx_administrators_status ON administrators(status);

