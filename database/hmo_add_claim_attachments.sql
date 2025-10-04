-- Add Attachments column to HMOClaims
ALTER TABLE `HMOClaims`
  ADD COLUMN `Attachments` TEXT NULL DEFAULT NULL AFTER `Remarks`;

-- Note: Attachments will store a JSON array of file path strings (relative to project root `uploads/hmo_claims/...`).
