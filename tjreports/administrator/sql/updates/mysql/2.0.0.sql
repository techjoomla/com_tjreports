-- Adding default values to the column of existing table

ALTER TABLE `#__tj_reports`	CHANGE `asset_id` `asset_id` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_reports` CHANGE `ordering` `ordering` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_reports` CHANGE `title` `title` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `#__tj_reports` CHANGE `alias` `alias` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `#__tj_reports` CHANGE `plugin` `plugin` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `#__tj_reports` CHANGE `client` `client` varchar(255) NOT NULL DEFAULT '';
ALTER TABLE `#__tj_reports` CHANGE `parent` `parent` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_reports` CHANGE `default` `default` tinyint(4) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_reports` CHANGE `userid` `userid` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `#__tj_reports` CHANGE `datadenyset` `datadenyset` int(11) DEFAULT 0;
ALTER TABLE `#__tj_reports` CHANGE `param` `param` text DEFAULT NULL;
