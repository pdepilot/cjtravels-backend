-- Login Rate Limiting Table
CREATE TABLE IF NOT EXISTS login_throttles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key_hash VARCHAR(64) UNIQUE NOT NULL,
    identifier VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 1,
    last_attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    locked_until TIMESTAMP NULL
);

CREATE INDEX IF NOT EXISTS idx_throttles_key ON login_throttles(key_hash);

