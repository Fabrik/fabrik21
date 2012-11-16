CREATE TABLE IF NOT EXISTS  `#__fabrik_digg` (
	`user_id` VARCHAR( 255 ) NOT NULL ,
	`tableid` INT( 6 ) NOT NULL ,
	`formid` INT( 6 ) NOT NULL ,
	`row_id` INT( 6 ) NOT NULL ,
	`comment_id` INT( 6 ) NOT NULL ,
	`date_created` DATETIME NOT NULL,
	 PRIMARY KEY ( `user_id` , `tableid` , `formid` , `row_id`, `comment_id` )
);