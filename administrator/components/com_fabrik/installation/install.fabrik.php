<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// Try extending time, as unziping/ftping took already quite some... :
@set_time_limit(240);

$memMax =	trim(@ini_get('memory_limit'));
if ($memMax) {
	$last =	strtolower($memMax{strlen($memMax) - 1});
	switch ($last) {
		case 'g':
			$memMax	*=	1024;
		case 'm':
			$memMax	*=	1024;
		case 'k':
			$memMax	*=	1024;
	}
	if ($memMax < 16000000) {
		@ini_set('memory_limit', '16M');
	}
	if ($memMax < 32000000) {
		@ini_set('memory_limit', '32M');
	}
	if ($memMax < 48000000) {
		@ini_set('memory_limit', '48M');		// DOMIT XML parser can be very memory-hungry on PHP < 5.1.3
	}
}
@ini_set('memory_limit', '64M');
@ini_set('max_execution_time', 380);
ignore_user_abort( true);

function com_install()
{
	//@TODO only run this when installing for the first time
	$app =& JFactory::getApplication();
	$db =& JFactory::getDBO();

	if (version_compare(phpversion(), '5.0.0', '<')) {
		echo 'Sorry you are using ' .  phpversion() . ". You need to have PHP5 installed to run Fabrik\n";
		return;
	}

	$db->setQuery("SELECT COUNT(*) FROM #__fabrik_connections");
	$c = $db->loadResult();

	$db->setQuery("SELECT `name` FROM #__fabrik_plugins");
	$currentplugins = $db->loadResultArray();
	if (is_null($currentplugins)) {
		$currentplugins = array();
	}
	//only load once (could be running from an upgrade)

	$array['element']['fabrikbutton'] 			= 'button';
	$array['element']['fabrikcheckbox'] 		= 'checkbox';
	$array['element']['fabrikdatabasejoin'] = 'database join';
	$array['element']['fabrikdate'] 				= 'date';
	$array['element']['fabrikdisplay'] 			= 'display text';
	$array['element']['fabrikdropdown'] 		= 'drop down';
	$array['element']['fabrikfield'] 				= 'field';
	$array['element']['fabrikfileupload'] 	= 'file upload';
	$array['element']['fabrikinternalid'] 	= 'id';
	$array['element']['fabrikimage'] 				= 'image';
	$array['element']['fabriklink'] 				= 'link';
	$array['element']['fabrikradiobutton'] 	= 'radio button';
	$array['element']['fabriktextarea'] 		= 'text area';
	$array['element']['fabrikuser'] 				= 'user';

	$array['form']['fabrikemail'] 					= 'email';
	$array['form']['fabrikreceipt'] 				= 'receipt';
	$array['form']['fabrikredirect'] 				= 'redirect';
	$array['form']['fabrikphp'] 						= 'Run PHP';

	$array['validationrule']['isalphanumeric'] = 'Is alpha-numeric';
	$array['validationrule']['isemail'] 		= 'Is email';
	$array['validationrule']['isnot'] 			= 'Is not';
	$array['validationrule']['isnumeric']		= 'Is numeric';
	$array['validationrule']['isuniquevalue'] = 'Is unique value';
	$array['validationrule']['notempty'] 		= 'Not empty';
	$array['validationrule']['php'] 				= 'PHP';
	$array['validationrule']['regex'] 			= 'Regular expression';

	$array['visualization']['calendar'] 		= 'calendar';
	$array['visualization']['googlemap'] 		= 'google map';
	$array['visualization']['chart'] 				= 'chart';
	$array['table']['copy'] 								= 'Copy';

	$array['cron']['cronemail']              = 'Email';

	foreach ($array as $group => $plugins) {
		foreach ($plugins as $name => $label) {
			if (!in_array($name, $currentplugins)) {
				$db->setQuery("INSERT INTO `#__fabrik_plugins` (name, label, type, state, iscore) VALUES ('$name', '$label', '$group', 1, 1);");
				$db->query();
			}
		}
	}

	if ($c == 0) {

		$sql = "insert into #__fabrik_connections (`host`,`user`,`password`,`database`,`description`,`state`, `default`) ";

		$sql .= "VALUES ('" . $app->getCfg('host') . "', " .
							 "\n '" . $app->getCfg('user') ."', " .
							 "\n '" . $app->getCfg('password') ."', " .
							 "\n '" . $app->getCfg('db') ."', " .
							 "\n'site database','1', '1')";

		$db->setQuery($sql);
		$db->query();

		// install event data

		$prefix = $app->getCfg('dbprefix');
		$db->setQuery("INSERT INTO `#__fabrik_tables` (`id` ,`label` ,`introduction` ,`form_id` ,`db_table_name` ,`db_primary_key` ,
		`auto_inc` ,`connection_id` ,`created` ,`created_by` ,`created_by_alias` ,`modified` ,`modified_by` ,`checked_out` ,
		`checked_out_time` ,`state` ,`publish_up` ,`publish_down` ,`access` ,`hits` ,
		`rows_per_page` ,`template` ,`order_by` ,`order_dir` ,`filter_action` ,`group_by` ,`private` ,`attribs`
		)
		VALUES (
		1 , 'Events', 'This table is used to store the calendar visualization events. Only super administrators can see and edit it in administration. ', '1', '{$prefix}fabrik_calendar_events', '`{$prefix}fabrik_calendar_events`.`id`', '1', '1', NULL , '62', 'fabrik', NULL , '', '', NULL , '1', NULL , NULL , '0', '0', '10', 'default', '{$prefix}fabrik_calendar_events.start_date', 'DESC', 'onchange', '', '1', 'admin_template=admin\ndetaillink=0
		empty_data_msg=sorry no data found
		advanced-filter=0
		show-table-nav=1
		show-table-filters=1
		show-table-add=1
		pdf=
		rss=0
		feed_title=
		feed_date=
		rsslimit=150
		rsslimitmax=2500
		csv_import_frontend=0
		csv_export_frontend=0
		csvfullname=0
		access=0
		allow_view_details=0
		allow_edit_details=18
		allow_add=18
		allow_delete=20
		group_by_order=
		group_by_order_dir=ASC
		prefilter_query='
		);");
		$res = $db->query();
		$tableid = $db->insertid();
		$db->setQuery("INSERT INTO `#__fabrik_forms` (
	`id` ,`label` ,`record_in_database` ,`error` ,`intro` ,`created` ,`created_by` ,`created_by_alias` ,
	`modified` ,`modified_by` ,`checked_out` ,`checked_out_time` ,`publish_up` ,
	`publish_down` ,`submit_button_label` ,`form_template` ,`view_only_template` ,`state` ,
	`attribs`
	)
	VALUES (
	1 , 'Add Event', '1', 'Some of the data is either missing or incorrect, please check your response and resubmit', '', '', '62', 'fabrik', '', '', '', '', NULL , NULL , 'Save', 'default', 'default', '1', 'reset_button=0
	reset_button_label=Reset
	copy_button=0
	copy_button_label=Save as a copy
	email_template=
	pdf=
	print=
	email='
	);");
		$res = $db->query();


		$db->setQuery("INSERT INTO `#__fabrik_groups` (
	`id` ,`name` ,`css` ,`label` ,`state` ,`created` ,`created_by` ,
	`created_by_alias` ,`modified` ,`modified_by` ,`checked_out` ,`checked_out_time`
	,`is_join` , `private`, `attribs`
	)
	VALUES (
	1 , 'events', '', '', '1', NOW( ) , '62', 'fabrik', '', '', '', '', '0', '1', 'repeat_group_button=0
	repeat_group_show_first=1
	repeat_group_js_add=
	repeat_group_js_delete='
	);");


		$res = $db->query();
		$groupid = $db->insertid();

		$db->setQuery("INSERT INTO `#__fabrik_formgroup` ( `id` ,`form_id` ,`group_id` ,`ordering`) VALUES ('1' , '1', '$groupid', '1');");
		$res = $db->query();

		$fields = "INSERT INTO #__fabrik_elements (`id`, `name`, `group_id`, `plugin`, `label`, `checked_out`, `checked_out_time`, `created`, `created_by`, `created_by_alias`, `modified`, `modified_by`, `width`, `height`, `default`, `hidden`, `eval`, `ordering`, `show_in_table_summary`, `can_order`, `filter_type`, `filter_exact_match`, `state`, `button_javascript`, `link_to_detail`, `primary_key`, `auto_increment`, `access`, `use_in_page_title`, `sub_values`, `sub_labels`, `sub_intial_selection`, `attribs`) VALUES";
		$db->setQuery($fields ."(1, 'visualization_id', $groupid, 'fabrikdatabasejoin', 'Visualization', 0, '0000-00-00 00:00:00', '2008-04-08 10:46:08', 62, 'admin', '0000-00-00 00:00:00', 0, 20, 0, '', 0, 0, 1, 1, 0, '', 0, 1, '', 0, 0, 0, 0, 0, '', '', '', 'rollover=\nhover_text_title=\ncomment=\npassword=0\nmaxlength=255\ntext_format=text\ninteger_length=6\ndecimal_length=2\ntext_format_string=\nguess_linktype=0\ndisable=0\nreadonly=0\nck_value=\nelement_before_label=0\noptions_per_row=4\nradio_element_before_label=0\nuse_wysiwyg=0\nallow_frontend_addtodropdown=0\nmultiple=0\ndrd_initial_selection=\nul_max_file_size=\nul_file_types=\nul_directory=\nul_email_file=0\nul_file_increment=0\nupload_allow_folderselect=1\ndefault_image=\nmake_link=0\nfu_show_image_in_table=0\nfu_show_image=0\nimage_library=gd2\nfu_main_max_width=\nfu_main_max_height=\nmake_thumbnail=0\nthumb_dir=\nthumb_prefix=\nthumb_max_height=\nthumb_max_width=\nselectImage_root_folder=-1\nimage_front_end_select=0\nshow_image_in_table=0\nimage_float=none\nlink_url=\nmy_data=id\nupdate_on_edit=0\ndate_table_format=Y-m-d\ndate_form_format=%Y-%m-%d\ndate_showtime=0\ndatabase_join_display_type=dropdown\nshow_both_with_radio_dbjoin=0\njoinType=simple\njoin_conn_id=1\njoin_db_name={$prefix}fabrik_visualizations\njoin_key_column=id\njoin_val_column=label\nadvJoin_concat=\nadvJoin_key=\nadvJoin_startTable=\ndatabase_join_where_sql=\ndatabase_join_noselectionvalue=\nfabrikdatabasejoin_frontend_add=0\nfabrikdatabasejoin_popupform=1\nlink_target=_self\nview_access=0\nshow_in_rss_feed=0\nshow_label_in_rss_feed=0\nuse_as_fake_key=0\nfilter_access=0\nfull_words_only=0\nicon_folder=images/stories/food\ncustom_link=\nsum_on=0\nsum_access=0\nsum_split=\navg_on=0\navg_access=0\navg_split=\nmedian_on=0\nmedian_access=0\ncount_on=0\nmedian_split=\ncount_condition=\ncount_access=0\ncount_split=')");
		$res = $db->query();
		$elid = $db->insertid();

		$db->setQuery("INSERT INTO #__fabrik_joins ".
	"(`table_id`, `element_id`, `join_from_table`, `table_join`, `table_key`, `table_join_key`, `join_type`, `group_id`, `attribs`)".
	" VALUES ".
	"($tableid, $elid, '{$prefix}fabrik_calendar_events', '{$prefix}fabrik_visualizations', 'visualization_id', 'id', 'left', '$groupid', 'join-label=label')");
		$db->query();

		$db->setQuery($fields ."(2, 'label', $groupid, 'fabrikfield', 'label', 0, '0000-00-00 00:00:00', '2008-04-08 10:46:25', 62, 'admin', '0000-00-00 00:00:00', 0, 20, 0, '', 0, 0, 2, 1, 0, '', 0, 1, '', 0, 0, 0, 0, 0, '', '', '', 'rollover=\nhover_text_title=\ncomment=\npassword=0\nmaxlength=255\ntext_format=text\ninteger_length=6\ndecimal_length=2\ntext_format_string=\nguess_linktype=0\ndisable=0\nreadonly=0\nck_value=\nelement_before_label=0\noptions_per_row=4\nradio_element_before_label=0\nuse_wysiwyg=0\nallow_frontend_addtodropdown=0\nmultiple=0\ndrd_initial_selection=\nul_max_file_size=\nul_file_types=\nul_directory=\nul_email_file=0\nul_file_increment=0\nupload_allow_folderselect=1\ndefault_image=\nmake_link=0\nfu_show_image_in_table=0\nfu_show_image=0\nimage_library=gd2\nfu_main_max_width=\nfu_main_max_height=\nmake_thumbnail=0\nthumb_dir=\nthumb_prefix=\nthumb_max_height=\nthumb_max_width=\nselectImage_root_folder=-1\nimage_front_end_select=0\nshow_image_in_table=0\nimage_float=none\nlink_url=\nmy_data=id\nupdate_on_edit=0\ndate_table_format=%Y-%m-%d\ndate_form_format=%Y-%m-%d\ndate_showtime=0\ndatabase_join_display_type=dropdown\nshow_both_with_radio_dbjoin=0\njoinType=simple\njoin_conn_id=-1\njoin_db_name=-1\njoin_key_column=\njoin_val_column=\nadvJoin_concat=\nadvJoin_key=\nadvJoin_startTable=\ndatabase_join_where_sql=\ndatabase_join_noselectionvalue=\nfabrikdatabasejoin_frontend_add=0\nfabrikdatabasejoin_popupform=1\nlink_target=_self\nview_access=0\nshow_in_rss_feed=0\nshow_label_in_rss_feed=0\nuse_as_fake_key=0\nfilter_access=0\nfull_words_only=0\nicon_folder=images/stories/food\ncustom_link=\nsum_on=0\nsum_access=0\nsum_split=\navg_on=0\navg_access=0\navg_split=\nmedian_on=0\nmedian_access=0\ncount_on=0\nmedian_split=\ncount_condition=\ncount_access=0\ncount_split=')");
		$res = $db->query();
		$db->setQuery($fields ."(3, 'start_date', $groupid, 'fabrikdate', 'start date', 0, '0000-00-00 00:00:00', '2008-04-08 10:46:52', 62, 'admin', '0000-00-00 00:00:00', 0, 20, 0, '', 0, 0, 3, 1, 0, '', 0, 1, '', 0, 0, 0, 0, 0, '', '', '', 'rollover=\nhover_text_title=\ncomment=\npassword=0\nmaxlength=255\ntext_format=text\ninteger_length=6\ndecimal_length=2\ntext_format_string=\nguess_linktype=0\ndisable=0\nreadonly=0\nck_value=\nelement_before_label=0\noptions_per_row=4\nradio_element_before_label=0\nuse_wysiwyg=0\nallow_frontend_addtodropdown=0\nmultiple=0\ndrd_initial_selection=\nul_max_file_size=\nul_file_types=\nul_directory=\nul_email_file=0\nul_file_increment=0\nupload_allow_folderselect=1\ndefault_image=\nmake_link=0\nfu_show_image_in_table=0\nfu_show_image=0\nimage_library=gd2\nfu_main_max_width=\nfu_main_max_height=\nmake_thumbnail=0\nthumb_dir=\nthumb_prefix=\nthumb_max_height=\nthumb_max_width=\nselectImage_root_folder=-1\nimage_front_end_select=0\nshow_image_in_table=0\nimage_float=none\nlink_url=\nmy_data=id\nupdate_on_edit=0\ndate_table_format=%Y-%m-%d\ndate_form_format=%Y-%m-%d\ndate_showtime=1\ndatabase_join_display_type=dropdown\nshow_both_with_radio_dbjoin=0\njoinType=simple\njoin_conn_id=-1\njoin_db_name=-1\njoin_key_column=\njoin_val_column=\nadvJoin_concat=\nadvJoin_key=\nadvJoin_startTable=\ndatabase_join_where_sql=\ndatabase_join_noselectionvalue=\nfabrikdatabasejoin_frontend_add=0\nfabrikdatabasejoin_popupform=1\nlink_target=_self\nview_access=0\nshow_in_rss_feed=0\nshow_label_in_rss_feed=0\nuse_as_fake_key=0\nfilter_access=0\nfull_words_only=0\nicon_folder=images/stories/food\ncustom_link=\nsum_on=0\nsum_access=0\nsum_split=\navg_on=0\navg_access=0\navg_split=\nmedian_on=0\nmedian_access=0\ncount_on=0\nmedian_split=\ncount_condition=\ncount_access=0\ncount_split=')");
		$res = $db->query();
		$db->setQuery($fields ."(4, 'created_by', $groupid, 'fabrikuser', 'creator', 0, '0000-00-00 00:00:00', '2008-04-08 10:47:19', 62, 'admin', '0000-00-00 00:00:00', 0, 20, 0, '', 1, 0, 4, 1, 0, '', 0, 1, '', 0, 0, 0, 0, 0, '', '', '', 'rollover=\nhover_text_title=\ncomment=\npassword=0\nmaxlength=255\ntext_format=text\ninteger_length=6\ndecimal_length=2\ntext_format_string=\nguess_linktype=0\ndisable=0\nreadonly=0\nck_value=\nck_default_label=\nelement_before_label=0\noptions_per_row=4\nallow_frontend_addtocheckbox=0\nchk-allowadd-onlylabel=0\nchk-savenewadditions=0\nradio_element_before_label=0\nallow_frontend_addtoradio=0\nrad-allowadd-onlylabel=0\nrad-savenewadditions=0\nuse_wysiwyg=0\ntextarea-showmax=0\ntextarea-maxlength=255\nmultiple=0\nallow_frontend_addtodropdown=0\ndd-allowadd-onlylabel=0\ndd-savenewadditions=0\ndrd_initial_selection=\nul_max_file_size=16000\nul_file_types=\nul_directory=\nul_email_file=0\nul_file_increment=0\nupload_allow_folderselect=1\nfu_fancy_upload=0\nupload_delete_image=1\ndefault_image=\nmake_link=0\nfu_show_image_in_table=0\nfu_show_image=0\nimage_library=gd2\nfu_main_max_width=\nfu_main_max_height=\nmake_thumbnail=0\nthumb_dir=\nthumb_prefix=\nthumb_max_height=\nthumb_max_width=\nimagepath=/\nselectImage_root_folder=/\nimage_front_end_select=0\nshow_image_in_table=0\nimage_float=none\nlink_url=\nmy_table_data=id\nupdate_on_edit=0\ndate_table_format=%Y-%m-%d\ndate_form_format=%Y-%m-%d\ndate_showtime=0\ndate_time_format=%H:%M\ndate_defaulttotoday=0\ndate_firstday=0\ndatabase_join_display_type=dropdown\njoinType=simple\njoin_conn_id=-1\njoin_db_name=\njoin_key_column=\njoin_val_column=\ndatabase_join_where_sql=\ndiysql=\ndatabase_join_noselectionvalue=\nfabrikdatabasejoin_frontend_add=0\nyoffset=\nlink_target=_self\nview_access=0\nshow_in_rss_feed=0\nshow_label_in_rss_feed=0\nuse_as_fake_key=0\nelement_alt_table_heading=\nicon_folder=images/stories/food\ncustom_link=\nuse_as_row_class=0\nfilter_access=0\nfull_words_only=0\ninc_in_adv_search=1\nsum_on=0\nsum_access=0\nsum_split=\navg_on=\navg_access=0\navg_split=\nmedian_on=0\nmedian_access=0\nmedian_split=\ncount_on=0\ncount_condition=\ncount_access=0\ncount_split=\n')");
		$res = $db->query();

				$db->setQuery("INSERT INTO #__fabrik_joins ".
	"(`table_id`, `element_id`, `join_from_table`, `table_join`, `table_key`, `table_join_key`, `join_type`, `group_id`, `attribs`)".
	" VALUES ".
	"(0, 4, '', '#__users', 'created_by', 'id', 'left', '$groupid', 'join-label=name')");
		$db->query();

		$db->setQuery($fields ."(5, 'created_by_alias', $groupid, 'fabrikfield', 'created by alias', 0, '0000-00-00 00:00:00', '2008-04-08 10:48:30', 62, 'admin', '0000-00-00 00:00:00', 0, 20, 0, '\$user =& JFactory::getUser();return \$user->get(\'username\');', 1, 1, 5, 1, 0, '', 0, 1, '', 0, 0, 0, 0, 0, '', '', '', 'rollover=\nhover_text_title=\ncomment=\npassword=0\nmaxlength=255\ntext_format=text\ninteger_length=6\ndecimal_length=2\ntext_format_string=\nguess_linktype=0\ndisable=0\nreadonly=0\nck_value=\nelement_before_label=0\noptions_per_row=4\nradio_element_before_label=0\nuse_wysiwyg=0\nallow_frontend_addtodropdown=0\nmultiple=0\ndrd_initial_selection=\nul_max_file_size=\nul_file_types=\nul_directory=\nul_email_file=0\nul_file_increment=0\nupload_allow_folderselect=1\ndefault_image=\nmake_link=0\nfu_show_image_in_table=0\nfu_show_image=0\nimage_library=gd2\nfu_main_max_width=\nfu_main_max_height=\nmake_thumbnail=0\nthumb_dir=\nthumb_prefix=\nthumb_max_height=\nthumb_max_width=\nselectImage_root_folder=-1\nimage_front_end_select=0\nshow_image_in_table=0\nimage_float=none\nlink_url=\nmy_data=username\nupdate_on_edit=0\ndate_table_format=%Y-%m-%d\ndate_form_format=%Y-%m-%d\ndate_showtime=0\ndatabase_join_display_type=dropdown\nshow_both_with_radio_dbjoin=0\njoinType=simple\njoin_conn_id=-1\njoin_db_name=-1\njoin_key_column=\njoin_val_column=\nadvJoin_concat=\nadvJoin_key=\nadvJoin_startTable=\ndatabase_join_where_sql=\ndatabase_join_noselectionvalue=\nfabrikdatabasejoin_frontend_add=0\nfabrikdatabasejoin_popupform=1\nlink_target=_self\nview_access=0\nshow_in_rss_feed=0\nshow_label_in_rss_feed=0\nuse_as_fake_key=0\nfilter_access=0\nfull_words_only=0\nicon_folder=images/stories/food\ncustom_link=\nsum_on=0\nsum_access=0\nsum_split=\navg_on=0\navg_access=0\navg_split=\nmedian_on=0\nmedian_access=0\ncount_on=0\nmedian_split=\ncount_condition=\ncount_access=0\ncount_split=')");
		$res = $db->query();
		$db->setQuery($fields ."(6, 'description', $groupid, 'fabriktextarea', 'description', 0, '0000-00-00 00:00:00', '2008-04-08 10:48:51', 62, 'admin', '0000-00-00 00:00:00', 0, 20, 3, '', 0, 0, 6, 1, 0, '', 0, 1, '', 0, 0, 0, 0, 0, '', '', '', 'rollover=\nhover_text_title=\ncomment=\npassword=0\nmaxlength=255\ntext_format=text\ninteger_length=6\ndecimal_length=2\ntext_format_string=\nguess_linktype=0\ndisable=0\nreadonly=0\nck_value=\nelement_before_label=0\noptions_per_row=4\nradio_element_before_label=0\nuse_wysiwyg=0\nallow_frontend_addtodropdown=0\nmultiple=0\ndrd_initial_selection=\nul_max_file_size=\nul_file_types=\nul_directory=\nul_email_file=0\nul_file_increment=0\nupload_allow_folderselect=1\ndefault_image=\nmake_link=0\nfu_show_image_in_table=0\nfu_show_image=0\nimage_library=gd2\nfu_main_max_width=\nfu_main_max_height=\nmake_thumbnail=0\nthumb_dir=\nthumb_prefix=\nthumb_max_height=\nthumb_max_width=\nselectImage_root_folder=-1\nimage_front_end_select=0\nshow_image_in_table=0\nimage_float=none\nlink_url=\nmy_data=id\nupdate_on_edit=0\ndate_table_format=%Y-%m-%d\ndate_form_format=%Y-%m-%d\ndate_showtime=0\ndatabase_join_display_type=dropdown\nshow_both_with_radio_dbjoin=0\njoinType=simple\njoin_conn_id=-1\njoin_db_name=-1\njoin_key_column=\njoin_val_column=\nadvJoin_concat=\nadvJoin_key=\nadvJoin_startTable=\ndatabase_join_where_sql=\ndatabase_join_noselectionvalue=\nfabrikdatabasejoin_frontend_add=0\nfabrikdatabasejoin_popupform=1\nlink_target=_self\nview_access=0\nshow_in_rss_feed=0\nshow_label_in_rss_feed=0\nuse_as_fake_key=0\nfilter_access=0\nfull_words_only=0\nicon_folder=images/stories/food\ncustom_link=\nsum_on=0\nsum_access=0\nsum_split=\navg_on=0\navg_access=0\navg_split=\nmedian_on=0\nmedian_access=0\ncount_on=0\nmedian_split=\ncount_condition=\ncount_access=0\ncount_split=')");
		$res = $db->query();


	}
	//always update the icons otherwise upgrade will remove image references

	/* Set up new icons for admin menu */
	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/fab_logo.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Fabrik'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/connections.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Connections'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/tables.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Tables'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/forms.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Forms'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/groups.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Groups'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/elements.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Elements'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/validations.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Form Validations'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/validation_rules.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Validation Rules'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/plugin.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Plugins'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/schedule.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Schedule'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/visualization.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Visualizations'");
	$res = $db->query();

	$db->setQuery("UPDATE #__components SET admin_menu_img='../administrator/components/com_fabrik/images/package.png' WHERE admin_menu_link LIKE 'option=com_fabrik%' AND admin_menu_alt='Packages'");
	$res = $db->query();


	//update the table's order_by col to allow for multiple order bys
	$db->setQuery("ALTER TABLE `#__fabrik_tables` CHANGE `order_dir` `order_dir` VARCHAR( 255 ) NOT NULL DEFAULT 'ASC'");
	$db->query();

	//test to ensure that the main component params have a default setup


	$db->setQuery("SELECT id, params FROM #__components WHERE name = 'Fabrik'");
	$row = $db->loadObject();
	if ($row) {
		if($row->params == '') {
			$row->params = 'fbConf_wysiwyg_label=0
fbConf_alter_existing_db_cols=1
remove_tables_on_unistall=1
usefabrik_mootools=0
merge_js=0
compress_js=none
spoofcheck_on_formsubmission=0
';
			$ok = $db->updateObject('#__components', $row, 'id', false);
		}
	}
	?>
<h3>Please wait whilst we uncompress some files, you should see 3 success messages appear below. Do not navigate away from this page until you do</h3>

<iframe src="index.php?option=com_fabrik&controller=home&task=endinstallation&format=raw" style="border:0;width:100%"></iframe>

<?php }?>