-- Migration: Extend trips table with multi-mode support
-- Purpose: Add selected_mode, cost tracking, and segments storage
-- Backward compatible: new columns are nullable/have defaults

ALTER TABLE trips 
  ADD COLUMN IF NOT EXISTS selected_mode 
    ENUM('car', 'ev', 'bus', 'train', 'flight') 
    DEFAULT NULL
    COMMENT 'Selected transportation mode for this trip'
    AFTER duration_s,
  ADD COLUMN IF NOT EXISTS source_name 
    VARCHAR(150) 
    DEFAULT NULL
    COMMENT 'Human-readable source location name'
    AFTER dest_lng,
  ADD COLUMN IF NOT EXISTS dest_name 
    VARCHAR(150) 
    DEFAULT NULL
    COMMENT 'Human-readable destination location name'
    AFTER source_name,
  ADD COLUMN IF NOT EXISTS cost_amount 
    DECIMAL(10, 2) 
    DEFAULT NULL
    COMMENT 'Trip cost in rupees'
    AFTER dest_name,
  ADD COLUMN IF NOT EXISTS segments_json 
    JSON 
    DEFAULT NULL
    COMMENT 'Array of trip segments (road/rail/flight)'
    AFTER cost_amount,
  ADD INDEX IF NOT EXISTS idx_mode (selected_mode),
  ADD INDEX IF NOT EXISTS idx_cost (cost_amount);
