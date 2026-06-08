-- ============================================================
-- Xoos Digital — Notes Module Schema
-- Run this in the Supabase Dashboard SQL Editor
-- ============================================================

CREATE TABLE IF NOT EXISTS notes (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT DEFAULT '',
    category VARCHAR(100) DEFAULT '',
    is_pinned SMALLINT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS note_checklist_items (
    id SERIAL PRIMARY KEY,
    note_id INT NOT NULL REFERENCES notes(id) ON DELETE CASCADE,
    text VARCHAR(255) NOT NULL,
    is_checked SMALLINT DEFAULT 0,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- updated_at trigger for notes
CREATE TRIGGER trg_notes_updated_at
    BEFORE UPDATE ON notes FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Index for faster queries
CREATE INDEX IF NOT EXISTS idx_notes_category ON notes (category);
CREATE INDEX IF NOT EXISTS idx_notes_pinned ON notes (is_pinned);
CREATE INDEX IF NOT EXISTS idx_note_checklist_items_note_id ON note_checklist_items (note_id);
