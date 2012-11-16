/*
Example tables for Fabrik subscription system

Date: 2011-11-01 23:52:13
*/

SET FOREIGN_KEY_CHECKS=0;
-- ----------------------------
-- Table structure for `fabsubs_emails`
-- ----------------------------
DROP TABLE IF EXISTS `fabsubs_emails`;
CREATE TABLE `fabsubs_emails` (
  `id` int(11) NOT NULL auto_increment,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `event_type` varchar(20) NOT NULL,
  `timeunit` varchar(2) NOT NULL,
  `time_value` int(3) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `fb_filter_event_type_INDEX` (`event_type`(10))
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of fabsubs_emails
-- ----------------------------
INSERT INTO `fabsubs_emails` VALUES ('1', 'Yoursite.com subscription renewal', '<p>Dear {name}</p>\r\n<p>Your Your Product membership for the account <strong>{username}</strong> will be <strong>automatically renewed in {daysleft} days</strong>, at {renew_date} GMT</p>\r\n<p>We hope that you have enjoyed using Your Product and would like to thank you for the\r\nsupport you have made to our community.\r\n<br />Paypal will <strong>automatically renew your subscription</strong> on this date (unless you have already cancelled the subscription)</p>\r\n<p>If you have not previously done so, and wish to <strong>cancel your subscription</strong> then please follow the instructions on this <a href=\"http://Yoursite.com/help/faq/3-subscriptions/3-how-can-i-cancel-my-subscription\">F.A.Q</a>\r\n</p>\r\n\r\n<p>Cancelling your subscription will simply stop its renewal and will not alter the remaining time left on your current subscription</p>\r\n<p>If you receieved this email by mistake, please contact me at <a href=\'mailto:your@email.address\'>your@email.address</a> providing me with your account username so that I can remove you from this notification list.</p>\r\n\r\n\r\n\r\n<p>Warm regards, <br />Your Name<br /><a href=\'http://Yoursite.com\'>Yoursite.com</a></p>\r\n<p><a href=\"http://Yoursite.com/subscribe/terms-and-conditions\">terms and conditions</a></p>', 'auto_renewal', 'D', '1');
INSERT INTO `fabsubs_emails` VALUES ('5', 'Yoursite.com subscription renewal', '<p>Dear {name}</p>\r\n<p>Your Your Product membership for the account <strong>{username}</strong> will be <strong>automatically renewed in {daysleft} days</strong>, at {renew_date} GMT</p>\r\n<p>We hope that you have enjoyed using Your Product and would like to thank you for the\r\nsupport you have made to our community.\r\n<br />Paypal will <strong>automatically renew your subscription</strong> on this date (unless you have already cancelled the subscription)</p>\r\n<p>If you have not previously done so, and wish to <strong>cancel your subscription</strong> then please follow the instructions on this <a href=\"http://Yoursite.com/help/faq/3-subscriptions/3-how-can-i-cancel-my-subscription\">F.A.Q</a>\r\n</p>\r\n\r\n<p>Cancelling your subscription will simply stop its renewal and will not alter the remaining time left on your current subscription</p>\r\n<p>If you receieved this email by mistake, please contact me at <a href=\'mailto:your@email.address\'>your@email.address</a> providing me with your account username so that I can remove you from this notification list.</p>\r\n\r\n\r\n\r\n<p>Warm regards, <br />Your Name<br /><a href=\'http://Yoursite.com\'>Yoursite.com</a></p>\r\n<p><a href=\"http://Yoursite.com/subscribe/terms-and-conditions\">terms and conditions</a></p>', 'auto_renewal', 'M', '1');
INSERT INTO `fabsubs_emails` VALUES ('3', 'Yoursite.com subscription expiration', '<p>Dear {name}</p>\r\n<p>Your Your Product membership for the account <strong>{username}</strong> will be expire in {daysleft} days</strong>, at {renew_date} GMT</p>\r\n<p>We hope that you have enjoyed using Your Product and would like to thank you for the\r\nsupport you have made to our community.\r\n</p>\r\n<p>If you wish to renew your subscription, please do so <a href=\"http://yoursite.com\">here</a>.</p>\r\n<p>If you have already renewed your subscription please ignore this email</p>\r\n\r\n\r\n<p>Warm regards, <br />Your name<br /><a href=\'http://Yoursite.com\'>Yoursite.com</a></p>\r\n<p><a href=\"http://Yoursite.com/subscribe/terms-and-conditions\">terms and conditions</a></p>', 'expiration', 'D', '1');
INSERT INTO `fabsubs_emails` VALUES ('4', 'Yoursite.com subscription renewal', '<p>Dear {name}</p>\r\n<p>Your Your Product membership for the account <strong>{username}</strong> will be <strong>automatically renewed in {daysleft} days</strong>, at {renew_date} GMT</p>\r\n<p>We hope that you have enjoyed using Your Product and would like to thank you for the\r\nsupport you have made to our community.\r\n<br />Paypal will <strong>automatically renew your subscription</strong> on this date (unless you have already cancelled the subscription)</p>\r\n<p>If you have not previously done so, and wish to <strong>cancel your subscription</strong> then please follow the instructions on this <a href=\"http://Yoursite.com/help/faq/3-subscriptions/3-how-can-i-cancel-my-subscription\">F.A.Q</a>\r\n</p>\r\n\r\n<p>Cancelling your subscription will simply stop its renewal and will not alter the remaining time left on your current subscription</p>\r\n<p>If you receieved this email by mistake, please contact me at <a href=\'mailto:your@email.address\'>your@email.address</a> providing me with your account username so that I can remove you from this notification list.</p>\r\n\r\n\r\n\r\n<p>Warm regards, <br />Your Name<br /><a href=\'http://Yoursite.com\'>Yoursite.com</a></p>\r\n<p><a href=\"http://Yoursite.com/subscribe/terms-and-conditions\">terms and conditions</a></p>', 'auto_renewal', 'W', '1');
INSERT INTO `fabsubs_emails` VALUES ('6', 'Yoursite.com subscription expiration', '<p>Dear {name}</p>\r\n<p>Your Your Product membership for the account <strong>{username}</strong> will be expire in {daysleft} days</strong>, at {renew_date} GMT</p>\r\n<p>We hope that you have enjoyed using Your Product and would like to thank you for the\r\nsupport you have made to our community.\r\n</p>\r\n<p>If you wish to renew your subscription, please do so <a href=\"http://yoursite.com\">here</a>.</p>\r\n\r\n<p>If you have already renewed your subscription please ignore this email</p>\r\n\r\n<p>Warm regards, <br />Your Name<br /><a href=\'http://Yoursite.com\'>Yoursite.com</a></p>\r\n<p><a href=\"http://Yoursite.com/subscribe/terms-and-conditions\">terms and conditions</a></p>', 'expiration', 'W', '1');
INSERT INTO `fabsubs_emails` VALUES ('7', 'Yoursite.com subscription expiration', '<p>Dear {name}</p>\r\n<p>Your Your Product membership for the account <strong>{username}</strong> will be expire in {daysleft} days</strong>, at {renew_date} GMT</p>\r\n<p>We hope that you have enjoyed using Your Product and would like to thank you for the\r\nsupport you have made to our community.\r\n</p>\r\n<p>If you wish to renew your subscription, please do so <a href=\"http://yoursite.com\">here</a>.</p>\r\n\r\n<p>If you have already renewed your subscription please ignore this email</p>\r\n\r\n<p>Warm regards, <br />Your name<br /><a href=\'http://Yoursite.com\'>Yoursite.com</a></p>\r\n<p><a href=\"http://Yoursite.com/subscribe/terms-and-conditions\">terms and conditions</a></p>', 'expiration', 'M', '1');

-- ----------------------------
-- Table structure for `fabsubs_invoices`
-- ----------------------------
DROP TABLE IF EXISTS `fabsubs_invoices`;
CREATE TABLE `fabsubs_invoices` (
  `id` int(6) NOT NULL auto_increment,
  `subscr_id` int(6) default NULL,
  `invoice_number` varchar(255) default NULL,
  `created_date` datetime default '0000-00-00 00:00:00',
  `transaction_date` datetime default '0000-00-00 00:00:00',
  `gateway_id` int(6) default NULL,
  `amount` varchar(40) default NULL,
  `currency` varchar(10) default NULL,
  `paid` text,
  `pp_txn_id` varchar(255) default NULL,
  `pp_payment_amount` varchar(255) default NULL,
  `pp_payment_status` varchar(255) default NULL,
  `pp_txn_type` varchar(255) default NULL,
  `pp_fee` varchar(255) default NULL,
  `pp_payer_email` varchar(255) default NULL,
  PRIMARY KEY  (`id`),
  KEY `fb_prefilter_method_INDEX` (`gateway_id`),
  KEY `fb_groupby_id_INDEX` (`id`),
  KEY `fb_filter_invoice_number_INDEX` (`invoice_number`(10)),
  KEY `fb_filter_subscr_id_INDEX` (`subscr_id`),
  KEY `fb_filter_pp_payer_email_INDEX` (`pp_payer_email`(10))
) ENGINE=MyISAM AUTO_INCREMENT=6792 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Table structure for `fabsubs_payment_gateways`
-- ----------------------------
DROP TABLE IF EXISTS `fabsubs_payment_gateways`;
CREATE TABLE `fabsubs_payment_gateways` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `active` text,
  `description` text,
  `subscription` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of fabsubs_payment_gateways
-- ----------------------------
INSERT INTO `fabsubs_payment_gateways` VALUES ('1', 'PayPal (single payment)', '1', '<p>The \'single payment\' option is a non-recurring payment which gives you access for the length of a single subscription period, and does not automatically renew.</p>\r\n<p>PayPal lets you send money to anyone with email. PayPal is free for consumers and works seamlessly with your existing credit card and checking account.</p>', '0');
INSERT INTO `fabsubs_payment_gateways` VALUES ('2', 'PayPal (recurring subscription)', '1', '<p>PayPal Subscription is the Subscription Service that will <strong>automatically bill your account each subscription period</strong>.</p>\r\n<p>You can cancel a subscription any time you want from your PayPal account. PayPal is free for consumers and works seamlessly with your existing credit card and checking account.</p>', '1');

-- ----------------------------
-- Table structure for `fabsubs_plan_billing_cycle`
-- ----------------------------
DROP TABLE IF EXISTS `fabsubs_plan_billing_cycle`;
CREATE TABLE `fabsubs_plan_billing_cycle` (
  `id` int(6) NOT NULL auto_increment,
  `plan_id` int(6) NOT NULL,
  `duration` int(6) NOT NULL,
  `period_unit` char(1) NOT NULL,
  `cost` int(6) NOT NULL,
  `currency` char(4) NOT NULL,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Records of fabsubs_plan_billing_cycle
-- ----------------------------

-- ----------------------------
-- Table structure for `fabsubs_plans`
-- ----------------------------
DROP TABLE IF EXISTS `fabsubs_plans`;
CREATE TABLE `fabsubs_plans` (
  `id` int(11) NOT NULL auto_increment,
  `active` text,
  `visible` text,
  `ordering` int(11) NOT NULL default '999999',
  `plan_name` varchar(255) default NULL,
  `desc` text,
  `email_desc` text,
  `params` text,
  `cost` int(6) default NULL,
  `currency` varchar(255) default NULL,
  `duration` int(6) default NULL,
  `period_unit` varchar(255) default NULL,
  `usergroup` int(11) default NULL,
  `fall_back_plan` int(6) NOT NULL COMMENT 'when this sub expires what subscription should the user get assigned to',
  `free` text,
  PRIMARY KEY  (`id`),
  KEY `fb_prefilter_active_INDEX` (`active`(10)),
  KEY `fb_prefilter_visible_INDEX` (`visible`(10))
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of fabsubs_plans
-- ----------------------------
INSERT INTO `fabsubs_plans` VALUES ('1', '1', '1', '3', 'Bronze Supporter', '<h3>&euro;49/year</h3>\r\n<hr />\r\n<h4>Get the MOST out of Fabrik!</h4>\r\n<hr />\r\n<ul>\r\n<li>5% OS development discount \r\n<ul style=\"display: none\">\r\n<li>Let us licence any custom code you contract us to write, and we\'ll give you a 5% discount </li>\r\n</ul>\r\n</li>\r\n<li>Forum support</li>\r\n<li>Full video Tutorials \r\n<ul style=\"display: none\">\r\n<li>In addition to the basic tutorials we have</li>\r\n<li>Joining data</li>\r\n<li>Table grouping</li>\r\n<li>Repeatable groups</li>\r\n<li>Table filtering</li>\r\n<li>Creating user profiles in Fabrik</li>\r\n<li>Using the Google map element &amp; visualization</li>\r\n<li>Mastering access control</li>\r\n<li>Building Fabrik tables from existing database tables </li>\r\n<li>Using the cascading drop down element </li>\r\n</ul>\r\n</li>\r\n<li>Full PDF Documentation</li>\r\n<li>Exclusive Plug-ins \r\n<ul style=\"display: none\">\r\n<li>Time-line visualization</li>\r\n<li>Run PHP table plug-in</li>\r\n<li>Email table plug-in</li>\r\n<li>Update column table plug-in</li>\r\n<li>Calculation element plug-in</li>\r\n<li>Google map element plug-in</li>\r\n<li>Cascading dropdown element plug-in</li>\r\n<li>Twitter form plug-in</li>\r\n<li>Paypal form plug-in</li>\r\n<li>Commenting plug-in</li>\r\n</ul>\r\n</li>\r\n</ul>', '', 'YToyNDp7czo5OiJmdWxsX2ZyZWUiO3M6MToiMCI7czoxMToiZnVsbF9hbW91bnQiO3M6NToiMjAuMDAiO3M6MTE6ImZ1bGxfcGVyaW9kIjtzOjE6IjEiO3M6MTU6ImZ1bGxfcGVyaW9kdW5pdCI7czoxOiJZIjtzOjEwOiJ0cmlhbF9mcmVlIjtzOjE6IjAiO3M6MTI6InRyaWFsX2Ftb3VudCI7czowOiIiO3M6MTI6InRyaWFsX3BlcmlvZCI7czowOiIiO3M6MTY6InRyaWFsX3BlcmlvZHVuaXQiO3M6MToiRCI7czoxMToiZ2lkX2VuYWJsZWQiO3M6MToiMSI7czozOiJnaWQiO3M6MjoiMTkiO3M6ODoibGlmZXRpbWUiO3M6MToiMCI7czoxNToic3RhbmRhcmRfcGFyZW50IjtzOjE6IjAiO3M6ODoiZmFsbGJhY2siO3M6MToiMiI7czoxMToibWFrZV9hY3RpdmUiO3M6MToiMSI7czoxMjoibWFrZV9wcmltYXJ5IjtzOjE6IjAiO3M6MTU6InVwZGF0ZV9leGlzdGluZyI7czoxOiIxIjtzOjEyOiJjdXN0b210aGFua3MiO3M6MDoiIjtzOjMwOiJjdXN0b210ZXh0X3RoYW5rc19rZWVwb3JpZ2luYWwiO3M6MToiMSI7czoxODoiY3VzdG9tYW1vdW50Zm9ybWF0IjtzOjM3Mzoie2FlY2pzb259eyJjbWQiOiJjb25kaXRpb24iLCJ2YXJzIjpbeyJjbWQiOiJkYXRhIiwidmFycyI6InBheW1lbnQuZnJlZXRyaWFsIn0seyJjbWQiOiJjb25jYXQiLCJ2YXJzIjpbeyJjbWQiOiJjb25zdGFudCIsInZhcnMiOiJfQ09ORklSTV9GUkVFVFJJQUwifSwiwqAiLHsiY21kIjoiZGF0YSIsInZhcnMiOiJwYXltZW50Lm1ldGhvZF9uYW1lIn1dfSx7ImNtZCI6ImNvbmNhdCIsInZhcnMiOlt7ImNtZCI6ImRhdGEiLCJ2YXJzIjoicGF5bWVudC5hbW91bnQifSx7ImNtZCI6ImRhdGEiLCJ2YXJzIjoicGF5bWVudC5jdXJyZW5jeV9zeW1ib2wifSwiwqAiLHsiY21kIjoiZGF0YSIsInZhcnMiOiJwYXltZW50Lm1ldGhvZF9uYW1lIn1dfV19ey9hZWNqc29ufSI7czoxNzoiY3VzdG9tdGV4dF90aGFua3MiO3M6MDoiIjtzOjE5OiJvdmVycmlkZV9hY3RpdmF0aW9uIjtzOjE6IjAiO3M6MTY6Im92ZXJyaWRlX3JlZ21haWwiO3M6MToiMCI7czoxNjoibm90YXV0aF9yZWRpcmVjdCI7czowOiIiO3M6MTA6InByb2Nlc3NvcnMiO2E6MTp7aTowO3M6MToiMiI7fX0=', '49', 'EUR', '1', 'Y', '19', '0', '0');
INSERT INTO `fabsubs_plans` VALUES ('2', '1', '1', '1', 'Free', '<h3>No really - free!</h3>\r\n<hr />\r\n<h4>Dip your toes in the Fabrik world</h4>\r\n<hr />\r\n<ul>\r\n<li>5% OS development discount   \r\n<ul style=\"display: none\">\r\n<li>Let us licence any custom code you contract us to write, and we\'ll give you a 5% discount </li>\r\n</ul>\r\n</li>\r\n<li>Community forum support</li>\r\n<li>Basic video Tutorials   \r\n<ul style=\"display: none\">\r\n<li>Currently this contains tutorials on:</li>\r\n<li>Creating tables</li>\r\n<li>Adding elements</li>\r\n<li>Creating a form</li>\r\n<li>Creating menu links </li>\r\n</ul>\r\n</li>\r\n</ul>', '', 'YToyMzp7czo5OiJmdWxsX2ZyZWUiO2k6MTtzOjExOiJmdWxsX2Ftb3VudCI7czo0OiIwLjAwIjtzOjExOiJmdWxsX3BlcmlvZCI7czowOiIiO3M6MTU6ImZ1bGxfcGVyaW9kdW5pdCI7czoxOiJEIjtzOjEwOiJ0cmlhbF9mcmVlIjtzOjE6IjAiO3M6MTI6InRyaWFsX2Ftb3VudCI7czowOiIiO3M6MTI6InRyaWFsX3BlcmlvZCI7czowOiIiO3M6MTY6InRyaWFsX3BlcmlvZHVuaXQiO3M6MToiRCI7czoxMToiZ2lkX2VuYWJsZWQiO3M6MToiMSI7czozOiJnaWQiO3M6MjoiMTgiO3M6ODoibGlmZXRpbWUiO3M6MToiMCI7czoxNToic3RhbmRhcmRfcGFyZW50IjtzOjE6IjAiO3M6ODoiZmFsbGJhY2siO3M6MToiMCI7czoxMToibWFrZV9hY3RpdmUiO3M6MToiMSI7czoxMjoibWFrZV9wcmltYXJ5IjtzOjE6IjEiO3M6MTU6InVwZGF0ZV9leGlzdGluZyI7czoxOiIxIjtzOjEyOiJjdXN0b210aGFua3MiO3M6MDoiIjtzOjMwOiJjdXN0b210ZXh0X3RoYW5rc19rZWVwb3JpZ2luYWwiO3M6MToiMSI7czoxODoiY3VzdG9tYW1vdW50Zm9ybWF0IjtzOjM3Mzoie2FlY2pzb259eyJjbWQiOiJjb25kaXRpb24iLCJ2YXJzIjpbeyJjbWQiOiJkYXRhIiwidmFycyI6InBheW1lbnQuZnJlZXRyaWFsIn0seyJjbWQiOiJjb25jYXQiLCJ2YXJzIjpbeyJjbWQiOiJjb25zdGFudCIsInZhcnMiOiJfQ09ORklSTV9GUkVFVFJJQUwifSwiwqAiLHsiY21kIjoiZGF0YSIsInZhcnMiOiJwYXltZW50Lm1ldGhvZF9uYW1lIn1dfSx7ImNtZCI6ImNvbmNhdCIsInZhcnMiOlt7ImNtZCI6ImRhdGEiLCJ2YXJzIjoicGF5bWVudC5hbW91bnQifSx7ImNtZCI6ImRhdGEiLCJ2YXJzIjoicGF5bWVudC5jdXJyZW5jeV9zeW1ib2wifSwiwqAiLHsiY21kIjoiZGF0YSIsInZhcnMiOiJwYXltZW50Lm1ldGhvZF9uYW1lIn1dfV19ey9hZWNqc29ufSI7czoxNzoiY3VzdG9tdGV4dF90aGFua3MiO3M6MDoiIjtzOjE5OiJvdmVycmlkZV9hY3RpdmF0aW9uIjtzOjE6IjAiO3M6MTY6Im92ZXJyaWRlX3JlZ21haWwiO3M6MToiMCI7czoxMDoicHJvY2Vzc29ycyI7czowOiIiO30=', '0', 'EUR', '0', 'D', '18', '0', '1');
INSERT INTO `fabsubs_plans` VALUES ('3', '1', '0', '3', 'Team', 'A current member of the team.', '', 'YToyMjp7czo5OiJmdWxsX2ZyZWUiO3M6MToiMSI7czoxMToiZnVsbF9hbW91bnQiO3M6NDoiMC4wMCI7czoxMToiZnVsbF9wZXJpb2QiO3M6MDoiIjtzOjE1OiJmdWxsX3BlcmlvZHVuaXQiO3M6MToiRCI7czoxMDoidHJpYWxfZnJlZSI7czoxOiIwIjtzOjEyOiJ0cmlhbF9hbW91bnQiO3M6MDoiIjtzOjEyOiJ0cmlhbF9wZXJpb2QiO3M6MDoiIjtzOjE2OiJ0cmlhbF9wZXJpb2R1bml0IjtzOjE6IkQiO3M6MTE6ImdpZF9lbmFibGVkIjtzOjE6IjEiO3M6MzoiZ2lkIjtzOjI6IjE5IjtzOjg6ImxpZmV0aW1lIjtzOjE6IjAiO3M6MTU6InN0YW5kYXJkX3BhcmVudCI7czoxOiIwIjtzOjg6ImZhbGxiYWNrIjtzOjE6IjEiO3M6MTE6Im1ha2VfYWN0aXZlIjtzOjE6IjEiO3M6MTI6Im1ha2VfcHJpbWFyeSI7czoxOiIxIjtzOjE1OiJ1cGRhdGVfZXhpc3RpbmciO3M6MToiMSI7czoxMjoiY3VzdG9tdGhhbmtzIjtzOjA6IiI7czozMDoiY3VzdG9tdGV4dF90aGFua3Nfa2VlcG9yaWdpbmFsIjtzOjE6IjEiO3M6MTc6ImN1c3RvbXRleHRfdGhhbmtzIjtzOjA6IiI7czoxOToib3ZlcnJpZGVfYWN0aXZhdGlvbiI7czoxOiIwIjtzOjE2OiJvdmVycmlkZV9yZWdtYWlsIjtzOjE6IjAiO3M6MTA6InByb2Nlc3NvcnMiO3M6MDoiIjt9', '0', 'EUR', null, null, null, '0', '0');
INSERT INTO `fabsubs_plans` VALUES ('4', '1', '1', '4', 'Silver Supporter', '<h3>&euro;120/quarter</h3>\r\n<hr />\r\n<h4>Need more hands on support?</h4>\r\n<hr />\r\n<ul>\r\n<li>5% OS development discount \r\n<ul style=\"display: none\">\r\n<li>Let us licence any custom code you contract us to write, and we\'ll give you a 5% discount </li>\r\n</ul>\r\n</li>\r\n<li>Forum support</li>\r\n<li>Full video Tutorials \r\n<ul style=\"display: none\">\r\n<li>In addition to the basic tutorials we have</li>\r\n<li>Joining data</li>\r\n<li>Table grouping</li>\r\n<li>Repeatable groups</li>\r\n<li>Table filtering</li>\r\n<li>Creating user profiles in Fabrik</li>\r\n<li>Using the Google map element &amp; visualization</li>\r\n<li>Mastering access control</li>\r\n<li>Building Fabrik tables from existing database tables </li>\r\n<li>Using the cascading drop down element </li>\r\n</ul>\r\n</li>\r\n<li>48 hour response time*</li>\r\n<li>Full PDF Documentation</li>\r\n<li>Exclusive Plug-ins \r\n<ul style=\"display: none\">\r\n<li>Time-line visualization</li>\r\n<li>Run PHP table plug-in</li>\r\n<li>Email table plug-in</li>\r\n<li>Update column table plug-in</li>\r\n<li>Calculation element plug-in</li>\r\n<li>Google map element plug-in</li>\r\n<li>Cascading dropdown element plug-in</li>\r\n<li>Twitter form plug-in</li>\r\n<li>Paypal form plug-in</li>\r\n<li>Commenting plug-in</li>\r\n</ul>\r\n</li>\r\n<li>5% development discount</li>\r\n<li>2 hours on server support</li>\r\n</ul>', '', 'YToyMzp7czo5OiJmdWxsX2ZyZWUiO3M6MToiMCI7czoxMToiZnVsbF9hbW91bnQiO3M6NToiODAuMDAiO3M6MTE6ImZ1bGxfcGVyaW9kIjtzOjE6IjMiO3M6MTU6ImZ1bGxfcGVyaW9kdW5pdCI7czoxOiJNIjtzOjEwOiJ0cmlhbF9mcmVlIjtzOjE6IjAiO3M6MTI6InRyaWFsX2Ftb3VudCI7czowOiIiO3M6MTI6InRyaWFsX3BlcmlvZCI7czowOiIiO3M6MTY6InRyaWFsX3BlcmlvZHVuaXQiO3M6MToiRCI7czoxMToiZ2lkX2VuYWJsZWQiO3M6MToiMSI7czozOiJnaWQiO3M6MjoiMjAiO3M6ODoibGlmZXRpbWUiO3M6MToiMCI7czoxNToic3RhbmRhcmRfcGFyZW50IjtzOjE6IjAiO3M6ODoiZmFsbGJhY2siO3M6MToiMSI7czoxMToibWFrZV9hY3RpdmUiO3M6MToiMSI7czoxMjoibWFrZV9wcmltYXJ5IjtzOjE6IjEiO3M6MTU6InVwZGF0ZV9leGlzdGluZyI7czoxOiIxIjtzOjEyOiJjdXN0b210aGFua3MiO3M6MDoiIjtzOjMwOiJjdXN0b210ZXh0X3RoYW5rc19rZWVwb3JpZ2luYWwiO3M6MToiMSI7czoxODoiY3VzdG9tYW1vdW50Zm9ybWF0IjtzOjM3Mzoie2FlY2pzb259eyJjbWQiOiJjb25kaXRpb24iLCJ2YXJzIjpbeyJjbWQiOiJkYXRhIiwidmFycyI6InBheW1lbnQuZnJlZXRyaWFsIn0seyJjbWQiOiJjb25jYXQiLCJ2YXJzIjpbeyJjbWQiOiJjb25zdGFudCIsInZhcnMiOiJfQ09ORklSTV9GUkVFVFJJQUwifSwiwqAiLHsiY21kIjoiZGF0YSIsInZhcnMiOiJwYXltZW50Lm1ldGhvZF9uYW1lIn1dfSx7ImNtZCI6ImNvbmNhdCIsInZhcnMiOlt7ImNtZCI6ImRhdGEiLCJ2YXJzIjoicGF5bWVudC5hbW91bnQifSx7ImNtZCI6ImRhdGEiLCJ2YXJzIjoicGF5bWVudC5jdXJyZW5jeV9zeW1ib2wifSwiwqAiLHsiY21kIjoiZGF0YSIsInZhcnMiOiJwYXltZW50Lm1ldGhvZF9uYW1lIn1dfV19ey9hZWNqc29ufSI7czoxNzoiY3VzdG9tdGV4dF90aGFua3MiO3M6MDoiIjtzOjE5OiJvdmVycmlkZV9hY3RpdmF0aW9uIjtzOjE6IjEiO3M6MTY6Im92ZXJyaWRlX3JlZ21haWwiO3M6MToiMCI7czoxMDoicHJvY2Vzc29ycyI7YToxOntpOjA7czoxOiIxIjt9fQ==', '120', 'EUR', '3', 'M', '20', '1', '0');
INSERT INTO `fabsubs_plans` VALUES ('7', '1', '0', '0', 'Supporter', '', null, null, '36', 'EUR', '1', 'Y', '19', '0', '0');
INSERT INTO `fabsubs_plans` VALUES ('8', '1', '0', '0', 'Standard', '', null, null, '15', 'EUR', '1', 'M', '20', '0', '0');
INSERT INTO `fabsubs_plans` VALUES ('9', '1', '0', '0', 'Professional', '', null, null, '40', 'EUR', '1', 'M', '23', '0', '0');

-- ----------------------------
-- Table structure for `fabsubs_subscriptions`
-- ----------------------------
DROP TABLE IF EXISTS `fabsubs_subscriptions`;
CREATE TABLE `fabsubs_subscriptions` (
  `id` int(6) NOT NULL auto_increment,
  `userid` int(11) default NULL,
  `primary` int(1) NOT NULL default '0',
  `type` int(6) default NULL,
  `status` varchar(10) default NULL,
  `signup_date` datetime default '0000-00-00 00:00:00',
  `lastpay_date` datetime default '0000-00-00 00:00:00',
  `cancel_date` datetime default '0000-00-00 00:00:00',
  `eot_date` datetime default '0000-00-00 00:00:00',
  `eot_cause` varchar(100) default NULL,
  `plan` int(6) default NULL,
  `recurring` int(1) NOT NULL default '0',
  `lifetime` int(1) NOT NULL default '0',
  `expiration` datetime default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  KEY `fb_filter_status_INDEX` (`status`),
  KEY `fb_order_userid_INDEX` (`userid`),
  KEY `fb_filter_userid_INDEX` (`userid`),
  KEY `fb_filter_plan_INDEX` (`plan`),
  KEY `fb_order_lastpay_date_INDEX` (`lastpay_date`),
  KEY `fb_filter_lastpay_date_INDEX` (`lastpay_date`),
  KEY `fb_filter_type_INDEX` (`type`),
  KEY `fb_order_signup_date_INDEX` (`signup_date`)
) ENGINE=MyISAM AUTO_INCREMENT=6742 DEFAULT CHARSET=utf8;


-- ----------------------------
-- Table structure for `fabsubs_users`
-- ----------------------------
DROP TABLE IF EXISTS `fabsubs_users`;
CREATE TABLE `fabsubs_users` (
  `fabrik_internal_id` int(11) NOT NULL auto_increment,
  `time_date` datetime default NULL,
  `userid` varchar(255) default NULL,
  `name` varchar(255) default NULL,
  `username` varchar(255) default NULL,
  `email` varchar(255) default NULL,
  `password` varchar(255) default NULL,
  `plan_id` int(6) default NULL,
  `terms` text,
  `termstext` text,
  `gateway` int(6) default NULL,
  `pp_txn_id` varchar(255) default NULL,
  `pp_payment_amount` varchar(255) default NULL,
  `pp_payment_status` varchar(255) default NULL,
  PRIMARY KEY  (`fabrik_internal_id`),
  KEY `fb_filter_userid_INDEX` (`userid`(10))
) ENGINE=MyISAM AUTO_INCREMENT=8798 DEFAULT CHARSET=latin1;

