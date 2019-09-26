CREATE TABLE IF NOT EXISTS `#__tjreports_com_users_user` (
  `record_id` int(11) NOT NULL,
  `username_hash` VARCHAR(255) NOT NULL,
  `email_hash` VARCHAR(255) NOT NULL
)ENGINE=InnoDB DEFAULT CHARSET=utf8;
