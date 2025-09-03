-- BTT Database Performance Optimization
-- Migration: 002_performance_optimization.sql
-- Adds additional indexes and optimizations for better query performance

-- Additional indexes for trips table
CREATE INDEX IF NOT EXISTS idx_trips_completed ON trips(completed);
CREATE INDEX IF NOT EXISTS idx_trips_favorite ON trips(favorite);
CREATE INDEX IF NOT EXISTS idx_trips_location ON trips(location);
CREATE INDEX IF NOT EXISTS idx_trips_difficulty ON trips(difficulty);
CREATE INDEX IF NOT EXISTS idx_trips_trip_type ON trips(trip_type);

-- Composite index for common query patterns
CREATE INDEX IF NOT EXISTS idx_trips_dates_completed ON trips(start_date, end_date, completed);

-- Additional indexes for gear_items table
CREATE INDEX IF NOT EXISTS idx_gear_items_weight ON gear_items(weight);
CREATE INDEX IF NOT EXISTS idx_gear_items_price ON gear_items(price);
CREATE INDEX IF NOT EXISTS idx_gear_items_brand ON gear_items(brand);

-- Additional indexes for backpack_gear relationship
CREATE INDEX IF NOT EXISTS idx_backpack_gear_section ON backpack_gear(section);
CREATE INDEX IF NOT EXISTS idx_backpack_gear_quantity ON backpack_gear(quantity);

-- Optimize backpacks table
CREATE INDEX IF NOT EXISTS idx_backpacks_capacity ON backpacks(capacity);
CREATE INDEX IF NOT EXISTS idx_backpacks_base_weight ON backpacks(base_weight);
CREATE INDEX IF NOT EXISTS idx_backpacks_created ON backpacks(created_at);

-- Add check constraints for data integrity (SQLite 3.3.0+)
-- Note: SQLite doesn't enforce CHECK constraints on existing data, only on new inserts/updates

-- Ensure positive values for numeric fields
-- These are informational in SQLite but document the expected constraints
/*
ALTER TABLE backpacks ADD CHECK (capacity > 0);
ALTER TABLE backpacks ADD CHECK (base_weight >= 0);
ALTER TABLE gear_items ADD CHECK (weight >= 0);
ALTER TABLE gear_items ADD CHECK (price >= 0);
ALTER TABLE trips ADD CHECK (distance >= 0);
ALTER TABLE trips ADD CHECK (elevation_gain >= 0);
ALTER TABLE backpack_gear ADD CHECK (quantity > 0);
ALTER TABLE trip_gear ADD CHECK (quantity > 0);
*/

-- Create views for common queries to improve readability and performance
CREATE VIEW IF NOT EXISTS v_trips_with_backpacks AS
SELECT 
    t.id,
    t.title,
    t.location,
    t.start_date,
    t.end_date,
    t.distance,
    t.distance_unit,
    t.elevation_gain,
    t.difficulty,
    t.trip_type,
    t.description,
    t.completed,
    t.favorite,
    t.image_url,
    t.image_alt,
    t.created_at,
    t.updated_at,
    b.id as backpack_id,
    b.name as backpack_name,
    b.capacity as backpack_capacity,
    b.base_weight as backpack_weight
FROM trips t
LEFT JOIN backpacks b ON t.backpack_id = b.id;

-- Create view for backpack statistics
CREATE VIEW IF NOT EXISTS v_backpack_stats AS
SELECT 
    b.id,
    b.name,
    b.capacity,
    b.base_weight,
    COUNT(DISTINCT bg.gear_id) as gear_count,
    SUM(bg.quantity) as total_items,
    COUNT(DISTINCT t.id) as trip_count,
    b.created_at,
    b.updated_at
FROM backpacks b
LEFT JOIN backpack_gear bg ON b.id = bg.backpack_id
LEFT JOIN trips t ON b.id = t.backpack_id
GROUP BY b.id;

-- Create view for gear usage statistics
CREATE VIEW IF NOT EXISTS v_gear_usage AS
SELECT 
    g.id,
    g.name,
    g.weight,
    g.category,
    g.brand,
    COUNT(DISTINCT bg.backpack_id) as used_in_backpacks,
    COUNT(DISTINCT tg.trip_id) as used_in_trips,
    SUM(COALESCE(bg.quantity, 0) + COALESCE(tg.quantity, 0)) as total_quantity
FROM gear_items g
LEFT JOIN backpack_gear bg ON g.id = bg.gear_id
LEFT JOIN trip_gear tg ON g.id = tg.gear_id
GROUP BY g.id;

-- Analyze tables to update SQLite's internal statistics
ANALYZE backpacks;
ANALYZE gear_items;
ANALYZE trips;
ANALYZE backpack_gear;
ANALYZE trip_gear;

-- Record migration
INSERT INTO migrations (filename, executed_at) 
VALUES ('002_performance_optimization.sql', datetime('now'));
