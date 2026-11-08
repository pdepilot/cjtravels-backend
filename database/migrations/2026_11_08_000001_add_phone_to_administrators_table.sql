-- Add phone column to administrators table
ALTER TABLE administrators ADD COLUMN phone VARCHAR(50) NULL;
CREATE INDEX IF NOT EXISTS idx_administrators_phone ON administrators(phone);

