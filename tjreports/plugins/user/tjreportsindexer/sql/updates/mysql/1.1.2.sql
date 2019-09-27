ALTER TABLE `#__tjreports_com_users_user` ADD username_hash VARCHAR(100) NOT NULL AFTER record_id;
ALTER TABLE `#__tjreports_com_users_user` ADD email_hash VARCHAR(100) NOT NULL AFTER username_hash;
