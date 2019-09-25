-- Change engine
ALTER TABLE `#__tj_reports` ENGINE = InnoDB;

-- Change charset, collation
ALTER TABLE `#__tj_reports` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

