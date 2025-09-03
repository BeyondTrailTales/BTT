-- BTT User Attachment to Existing Data v1.0.0
-- Migration: 0003_attach_user_to_existing.sql
-- Description: Add user_id columns to trips and backpacks, handle existing data

-- Enable foreign key constraints
PRAGMA foreign_keys = ON;

-- =====================================================
-- CREATE SCHEMA_MIGRATIONS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS schema_migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL UNIQUE,
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- CREATE ADMIN USER FOR EXISTING DATA
-- =====================================================
-- Create a default admin user for legacy data assignment
-- Password: BTTAdmin2025! (will need to be changed on first login)
-- Password hash created with password_hash('BTTAdmin2025!', PASSWORD_BCRYPT, ['cost' => 12])
INSERT OR IGNORE INTO users (email, username, password_hash, email_verified_at, created_at, updated_at)
VALUES (
    'admin@btt.local',
    'admin',
    '$2y$12$D1l1cVtvuinF5aZjE9BDu.w3bSFMsyqJObfH555qVIJph9huR3DJO',
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
);

-- =====================================================
-- HANDLE VIEWS THAT DEPEND ON TRIPS AND BACKPACKS
-- =====================================================
-- Store view definitions before dropping them
-- We'll recreate them after updating the tables

-- Drop views that depend on trips or backpacks tables
DROP VIEW IF EXISTS v_trips_with_backpacks;
DROP VIEW IF EXISTS v_backpack_stats;
DROP VIEW IF EXISTS v_gear_usage;

-- =====================================================
-- ADD USER_ID TO TRIPS TABLE
-- =====================================================
-- First, check if column exists to make migration idempotent
-- SQLite doesn't support IF NOT EXISTS for ALTER TABLE, so we need to handle this carefully

-- Create a temporary trips table with the new schema
CREATE TABLE IF NOT EXISTS trips_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    location TEXT,
    start_date TEXT,
    end_date TEXT,
    distance REAL,
    distance_unit TEXT DEFAULT 'miles',
    elevation_gain REAL,
    difficulty TEXT,
    trip_type TEXT,
    description TEXT,
    backpack_id INTEGER,
    completed INTEGER DEFAULT 0,
    favorite INTEGER DEFAULT 0,
    image_url TEXT,
    image_alt TEXT,
    user_id INTEGER NOT NULL,  -- New column
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backpack_id) REFERENCES backpacks(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Copy data from existing trips table if it exists
-- Assign all existing trips to the admin user
INSERT OR IGNORE INTO trips_new (
    id, title, location, start_date, end_date, distance, distance_unit,
    elevation_gain, difficulty, trip_type, description, backpack_id,
    completed, favorite, image_url, image_alt, user_id, created_at, updated_at
)
SELECT 
    id, title, location, start_date, end_date, distance, distance_unit,
    elevation_gain, difficulty, trip_type, description, backpack_id,
    completed, favorite, image_url, image_alt,
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1) as user_id,
    COALESCE(created_at, CURRENT_TIMESTAMP) as created_at,
    COALESCE(updated_at, CURRENT_TIMESTAMP) as updated_at
FROM trips
WHERE EXISTS (SELECT 1 FROM sqlite_master WHERE type='table' AND name='trips');

-- Drop the old trips table and rename the new one
DROP TABLE IF EXISTS trips;
ALTER TABLE trips_new RENAME TO trips;

-- Create indexes for trips
CREATE INDEX IF NOT EXISTS idx_trips_user_id ON trips(user_id);
CREATE INDEX IF NOT EXISTS idx_trips_backpack ON trips(backpack_id);
CREATE INDEX IF NOT EXISTS idx_trips_dates ON trips(start_date, end_date);

-- Create trigger for trips updated_at
CREATE TRIGGER IF NOT EXISTS trips_updated_at
    AFTER UPDATE ON trips
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE trips SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- =====================================================
-- ADD USER_ID TO BACKPACKS TABLE
-- =====================================================
-- Create a temporary backpacks table with the new schema
CREATE TABLE IF NOT EXISTS backpacks_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    capacity INTEGER DEFAULT 65,
    base_weight REAL DEFAULT 0,
    image_url TEXT,
    image_alt TEXT,
    user_id INTEGER NOT NULL,  -- New column
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Copy data from existing backpacks table if it exists
-- Assign all existing backpacks to the admin user
INSERT OR IGNORE INTO backpacks_new (
    id, name, description, capacity, base_weight, image_url, image_alt,
    user_id, created_at, updated_at
)
SELECT 
    id, name, description, capacity, base_weight, image_url, image_alt,
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1) as user_id,
    COALESCE(created_at, CURRENT_TIMESTAMP) as created_at,
    COALESCE(updated_at, CURRENT_TIMESTAMP) as updated_at
FROM backpacks
WHERE EXISTS (SELECT 1 FROM sqlite_master WHERE type='table' AND name='backpacks');

-- Drop the old backpacks table and rename the new one
DROP TABLE IF EXISTS backpacks;
ALTER TABLE backpacks_new RENAME TO backpacks;

-- Create indexes for backpacks
CREATE INDEX IF NOT EXISTS idx_backpacks_user_id ON backpacks(user_id);

-- Create trigger for backpacks updated_at
CREATE TRIGGER IF NOT EXISTS backpacks_updated_at
    AFTER UPDATE ON backpacks
    FOR EACH ROW
    WHEN NEW.updated_at = OLD.updated_at
BEGIN
    UPDATE backpacks SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- =====================================================
-- SET UP INITIAL COLLABORATORS FOR EXISTING DATA
-- =====================================================
-- Make admin the owner of all existing trips
INSERT OR IGNORE INTO trip_collaborators (trip_id, user_id, role, added_by)
SELECT 
    id as trip_id,
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1) as user_id,
    'owner' as role,
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1) as added_by
FROM trips;

-- Make admin the owner of all existing backpacks
INSERT OR IGNORE INTO backpack_collaborators (backpack_id, user_id, role, added_by)
SELECT 
    id as backpack_id,
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1) as user_id,
    'owner' as role,
    (SELECT id FROM users WHERE username = 'admin' LIMIT 1) as added_by
FROM backpacks;

-- =====================================================
-- RECREATE VIEWS AFTER TABLE MODIFICATIONS
-- =====================================================
-- Recreate views that were dropped at the beginning

-- v_trips_with_backpacks view
CREATE VIEW IF NOT EXISTS v_trips_with_backpacks AS
SELECT 
    t.id,
    t.title,
    t.location,
    t.start_date,
    t.end_date,
    t.distance,
    t.elevation_gain,
    t.difficulty,
    t.trip_type,
    t.description,
    t.backpack_id,
    t.completed,
    t.favorite,
    t.user_id,
    t.created_at,
    t.updated_at,
    b.name as backpack_name,
    b.capacity as backpack_capacity,
    b.base_weight as backpack_weight,
    u.username,
    u.email as user_email
FROM trips t
LEFT JOIN backpacks b ON t.backpack_id = b.id
LEFT JOIN users u ON t.user_id = u.id;

-- v_backpack_stats view
CREATE VIEW IF NOT EXISTS v_backpack_stats AS
SELECT 
    b.id,
    b.name,
    b.capacity,
    b.base_weight,
    b.user_id,
    COUNT(DISTINCT bg.gear_id) as total_items,
    SUM(gi.weight * bg.quantity) as total_weight,
    u.username
FROM backpacks b
LEFT JOIN backpack_gear bg ON b.id = bg.backpack_id
LEFT JOIN gear_items gi ON bg.gear_id = gi.id
LEFT JOIN users u ON b.user_id = u.id
GROUP BY b.id;

-- v_gear_usage view
CREATE VIEW IF NOT EXISTS v_gear_usage AS
SELECT 
    g.id,
    g.name,
    g.weight,
    g.category,
    g.brand,
    COUNT(DISTINCT bg.backpack_id) as used_in_backpacks,
    COUNT(DISTINCT tg.trip_id) as used_in_trips
FROM gear_items g
LEFT JOIN backpack_gear bg ON g.id = bg.gear_id
LEFT JOIN trip_gear tg ON g.id = tg.gear_id
GROUP BY g.id;

-- =====================================================
-- MIGRATION LOG
-- =====================================================
-- Log that this migration script creates its own record
-- This ensures idempotency as the runner will check for this
