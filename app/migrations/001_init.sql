-- BTT Database Schema v1.0.0
-- Initial migration: Create core tables for backpacks, trips, and gear

-- Enable foreign key constraints
PRAGMA foreign_keys = ON;

-- Backpacks table
CREATE TABLE IF NOT EXISTS backpacks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    capacity INTEGER DEFAULT 65,
    base_weight REAL DEFAULT 0,
    image_url TEXT,
    image_alt TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Gear items table
CREATE TABLE IF NOT EXISTS gear_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    weight REAL NOT NULL DEFAULT 0,
    category TEXT,
    brand TEXT,
    notes TEXT,
    price REAL DEFAULT 0,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Backpack gear relationship table
CREATE TABLE IF NOT EXISTS backpack_gear (
    backpack_id INTEGER NOT NULL,
    gear_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    section TEXT DEFAULT 'main',
    PRIMARY KEY (backpack_id, gear_id),
    FOREIGN KEY (backpack_id) REFERENCES backpacks(id) ON DELETE CASCADE,
    FOREIGN KEY (gear_id) REFERENCES gear_items(id) ON DELETE CASCADE
);

-- Trips table
CREATE TABLE IF NOT EXISTS trips (
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
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backpack_id) REFERENCES backpacks(id) ON DELETE SET NULL
);

-- Trip gear relationship table (for trip-specific gear)
CREATE TABLE IF NOT EXISTS trip_gear (
    trip_id INTEGER NOT NULL,
    gear_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    PRIMARY KEY (trip_id, gear_id),
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (gear_id) REFERENCES gear_items(id) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_backpack_gear_backpack ON backpack_gear(backpack_id);
CREATE INDEX IF NOT EXISTS idx_backpack_gear_gear ON backpack_gear(gear_id);
CREATE INDEX IF NOT EXISTS idx_trip_gear_trip ON trip_gear(trip_id);
CREATE INDEX IF NOT EXISTS idx_trip_gear_gear ON trip_gear(gear_id);
CREATE INDEX IF NOT EXISTS idx_trips_backpack ON trips(backpack_id);
CREATE INDEX IF NOT EXISTS idx_trips_dates ON trips(start_date, end_date);
CREATE INDEX IF NOT EXISTS idx_gear_category ON gear_items(category);

-- Migration tracking table
CREATE TABLE IF NOT EXISTS migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL UNIQUE,
    executed_at TEXT DEFAULT CURRENT_TIMESTAMP
);

-- Insert this migration record
INSERT INTO migrations (filename) VALUES ('001_init.sql');
