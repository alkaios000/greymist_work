-- Migration 03: Tambah kolom custom_js dan custom_breakpoints pada gm_projects dan gm_frames
-- File: database/migrations/03_add_custom_js_and_breakpoints.sql

ALTER TABLE `gm_projects`
  ADD COLUMN IF NOT EXISTS `custom_js` LONGTEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `custom_breakpoints` LONGTEXT DEFAULT NULL;

ALTER TABLE `gm_frames`
  ADD COLUMN IF NOT EXISTS `custom_breakpoints` LONGTEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `custom_js` LONGTEXT DEFAULT NULL;

