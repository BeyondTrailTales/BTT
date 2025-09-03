-- BTT Trips Sharing & Collaboration Schema v1.0.0
-- Migration: 0002_trips_sharing.sql
-- Description: Tables for trip sharing, collaboration, and real-time editing

-- Enable foreign key constraints
PRAGMA foreign_keys = ON;

-- =====================================================
-- SHARE_CODES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS share_codes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,  -- short unique code
    trip_id INTEGER NOT NULL,
    created_by INTEGER NOT NULL,
    max_uses INTEGER NOT NULL DEFAULT 1,
    uses INTEGER NOT NULL DEFAULT 0,
    expires_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for share_codes
CREATE UNIQUE INDEX IF NOT EXISTS idx_share_codes_code ON share_codes(code);
CREATE INDEX IF NOT EXISTS idx_share_codes_trip_id ON share_codes(trip_id);
CREATE INDEX IF NOT EXISTS idx_share_codes_created_by ON share_codes(created_by);
CREATE INDEX IF NOT EXISTS idx_share_codes_expires_at ON share_codes(expires_at);
CREATE INDEX IF NOT EXISTS idx_share_codes_revoked_at ON share_codes(revoked_at);

-- =====================================================
-- TRIP_SHARES TABLE (Audit of redeemed codes)
-- =====================================================
CREATE TABLE IF NOT EXISTS trip_shares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,  -- redeemer
    share_code_id INTEGER NOT NULL,
    redeemed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (share_code_id) REFERENCES share_codes(id) ON DELETE CASCADE
);

-- Unique index to prevent duplicate redemptions
CREATE UNIQUE INDEX IF NOT EXISTS idx_trip_shares_trip_user ON trip_shares(trip_id, user_id);
CREATE INDEX IF NOT EXISTS idx_trip_shares_share_code_id ON trip_shares(share_code_id);

-- =====================================================
-- TRIP_COLLABORATORS TABLE (Access Control List)
-- =====================================================
CREATE TABLE IF NOT EXISTS trip_collaborators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('owner', 'editor', 'viewer')),
    added_by INTEGER NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Unique index to prevent duplicate collaborator entries
CREATE UNIQUE INDEX IF NOT EXISTS idx_trip_collaborators_trip_user ON trip_collaborators(trip_id, user_id);
CREATE INDEX IF NOT EXISTS idx_trip_collaborators_role ON trip_collaborators(role);

-- =====================================================
-- TRIP_EDIT_EVENTS TABLE (Real-time collaboration)
-- =====================================================
CREATE TABLE IF NOT EXISTS trip_edit_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    version INTEGER NOT NULL,  -- monotonically increasing per trip
    op_type TEXT NOT NULL,  -- e.g., 'patch', 'field_update', 'bulk_update'
    payload TEXT NOT NULL,  -- JSON data
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for efficient event streaming
CREATE INDEX IF NOT EXISTS idx_trip_edit_events_trip_version ON trip_edit_events(trip_id, version);
CREATE INDEX IF NOT EXISTS idx_trip_edit_events_user_id ON trip_edit_events(user_id);
CREATE INDEX IF NOT EXISTS idx_trip_edit_events_created_at ON trip_edit_events(created_at);

-- =====================================================
-- TRIP_PRESENCE TABLE (Who's currently editing)
-- =====================================================
CREATE TABLE IF NOT EXISTS trip_presence (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    trip_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    last_heartbeat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cursor_position TEXT NULL,  -- JSON: current field/position
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Unique index for one presence record per user per trip
CREATE UNIQUE INDEX IF NOT EXISTS idx_trip_presence_trip_user ON trip_presence(trip_id, user_id);
CREATE INDEX IF NOT EXISTS idx_trip_presence_last_heartbeat ON trip_presence(last_heartbeat);

-- =====================================================
-- BACKPACK_COLLABORATORS TABLE (Access Control List for Backpacks)
-- =====================================================
CREATE TABLE IF NOT EXISTS backpack_collaborators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    backpack_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('owner', 'editor', 'viewer')),
    added_by INTEGER NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backpack_id) REFERENCES backpacks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Unique index to prevent duplicate collaborator entries
CREATE UNIQUE INDEX IF NOT EXISTS idx_backpack_collaborators_backpack_user ON backpack_collaborators(backpack_id, user_id);
CREATE INDEX IF NOT EXISTS idx_backpack_collaborators_role ON backpack_collaborators(role);

-- =====================================================
-- NOTIFICATION_QUEUE TABLE (For async notifications)
-- =====================================================
CREATE TABLE IF NOT EXISTS notification_queue (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL,  -- 'trip_shared', 'collaborator_added', 'trip_edited', etc.
    data TEXT NOT NULL,  -- JSON payload
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indexes for notification_queue
CREATE INDEX IF NOT EXISTS idx_notification_queue_user_id ON notification_queue(user_id);
CREATE INDEX IF NOT EXISTS idx_notification_queue_read_at ON notification_queue(read_at);
CREATE INDEX IF NOT EXISTS idx_notification_queue_created_at ON notification_queue(created_at);
