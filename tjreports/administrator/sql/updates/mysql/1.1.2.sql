-- Change engine
ALTER TABLE `#__tj_reports` ENGINE = InnoDB;

-- Change charset, collation for table #__tj_reports
ALTER TABLE `#__tj_reports` CHANGE `title` `title` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `#__tj_reports` CHANGE `alias` `alias` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `#__tj_reports` CHANGE `plugin` `plugin` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `#__tj_reports` CHANGE `client` `client` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `#__tj_reports` CHANGE `param` `param` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
