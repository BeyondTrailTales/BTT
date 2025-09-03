-- Migration: Add missing fields to trips and backpacks tables
-- Date: 2024-01-09
-- Purpose: Ensure all form fields can be saved to database

-- Add missing fields to trips table
ALTER TABLE trips ADD COLUMN permit_required INTEGER DEFAULT 0;
ALTER TABLE trips ADD COLUMN permit_info TEXT;
ALTER TABLE trips ADD COLUMN water_sources TEXT;
ALTER TABLE trips ADD COLUMN camping_type TEXT;
ALTER TABLE trips ADD COLUMN expected_weather TEXT;
ALTER TABLE trips ADD COLUMN trail_conditions TEXT;
ALTER TABLE trips ADD COLUMN emergency_contact TEXT;
ALTER TABLE trips ADD COLUMN trailhead_parking TEXT;
ALTER TABLE trips ADD COLUMN photo_path TEXT;
ALTER TABLE trips ADD COLUMN photo_alt_text TEXT;

-- Add missing fields to backpacks table
ALTER TABLE backpacks ADD COLUMN capacity_l REAL;
ALTER TABLE backpacks ADD COLUMN weight_empty_g REAL;
ALTER TABLE backpacks ADD COLUMN type TEXT DEFAULT 'custom';
ALTER TABLE backpacks ADD COLUMN tags TEXT;  -- Will store JSON array

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_trips_user_id ON trips(user_id);
CREATE INDEX IF NOT EXISTS idx_trips_completed ON trips(completed);
CREATE INDEX IF NOT EXISTS idx_trips_favorite ON trips(favorite);
CREATE INDEX IF NOT EXISTS idx_backpacks_user_id ON backpacks(user_id);
CREATE INDEX IF NOT EXISTS idx_backpacks_type ON backpacks(type);
