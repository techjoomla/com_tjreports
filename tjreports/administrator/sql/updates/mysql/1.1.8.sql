-- Adding default values to the column of existing table

ALTER TABLE `#__tj_reports`
	CHANGE `asset_id` `asset_id` int(11) NOT NULL DEFAULT '0',
	CHANGE `ordering` `ordering` int(11) NOT NULL DEFAULT '0',
	CHANGE `title` `title` varchar(255) NOT NULL DEFAULT '',
	CHANGE `alias` `alias` varchar(255) NOT NULL DEFAULT '',
	CHANGE `plugin` `plugin` varchar(255) NOT NULL DEFAULT '',
	CHANGE `client` `client` varchar(255) NOT NULL DEFAULT '',
	CHANGE `parent` `parent` int(11) NOT NULL DEFAULT '0',
	CHANGE `default` `default` tinyint(4) NOT NULL DEFAULT '0',
	CHANGE `userid` `userid` int(11) NOT NULL DEFAULT '0',
	CHANGE `datadenyset` `datadenyset` int(11) DEFAULT '0',
	CHANGE `param` `param` text NOT NULL DEFAULT '';
