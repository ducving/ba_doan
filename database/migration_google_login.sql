-- Migration to support Google Login
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL AFTER email;
ALTER TABLE users ADD COLUMN oauth_provider VARCHAR(50) NULL AFTER google_id;
CREATE INDEX idx_google_id ON users(google_id);
