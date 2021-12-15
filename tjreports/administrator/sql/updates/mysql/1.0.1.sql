CREATE TABLE IF NOT EXISTS `#__tj_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `alias` varchar(255) NOT NULL DEFAULT '',
  `plugin` varchar(255) NOT NULL DEFAULT '',
  `client` varchar(255) NOT NULL DEFAULT '',
  `parent` int(11) NOT NULL DEFAULT 0,
  `default` tinyint(4) NOT NULL DEFAULT 0,
  `userid` int(11) NOT NULL DEFAULT 0,
  `datadenyset` int(11) DEFAULT 0,
  `param` text,
  PRIMARY KEY (`id`)
) AUTO_INCREMENT=1 ;
