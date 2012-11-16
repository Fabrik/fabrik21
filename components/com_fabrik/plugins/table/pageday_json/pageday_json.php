<?php

/**
 * Add an action button to the table to copy rows
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-table.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');


class TableRequestLog extends JTable
{
	/** @var int primary key */
 	var $id = null;

 	/** @var string the ip address */
 	var $ipaddress = null;

 	/** @var datetime recorded */
 	var $date_time = null;

 	/** @var string is request for calendar list or calendar dates*/
 	var $request_for = null;

 	var $error = null;

 	/** @var string request data */
 	var $request = null;

 	/*
 	 * Constructor
 	 */

	function __construct( &$_db )
	{
		parent::__construct( 'pageday_calendar_requests', 'id', $_db);
	}

}

class FabrikModelPageday_json extends FabrikModelTablePlugin {

	var $_counter = null;
	//also set in iphone_instore_verify.php
	private $salt = 'asl;dfas09fuwaejfaskdl;fjasd0f=asdfmaslkdj sadf dx0[9ik54kljnf';
	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * run when the table loads its data(non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelTablePlugin#onLoadData($params, $oRequest)
	 */
	function onLoadData($params, &$model)
	{
		if (jRequest::getVar('format') != 'raw') {
			return;
		}
		if (JRequest::getInt('Itemid') == 57)
		{
			$this->getCalendarList($model);
		} else {
			$this->getCalendarDays($model);
		}
		$this->logRequest( $model);
	}

	function logRequest( &$model )
	{
		$date = JFactory::getDate();
		$row = JTable::getInstance('RequestLog', 'Table');
		$row->ipaddress = $_SERVER['REMOTE_ADDR'];
		$row->date_time = $date->toMySQL();
		$row->request_for = JRequest::getInt('Itemid') == 57 ? 'calendar list' : 'calendar days';
		$request = JRequest::get('request');
		$row->request = '<h1>Request Vars</h1>';
		foreach ($request as $key => $val) {
			$row->request .= "$key = $val\n";
		}
		$row->request .= '<h1>Server Vars</h1>';
		foreach ($_SERVER as $key => $val) {
			$row->request .= "$key = $val\n";
		}
		if (isset($model->_data->error)) {
			$row->error = $model->_data->error;
		}
		$row->store();
	}

	function getCalendarList(&$model )
	{
		$return = array();

		foreach($model->_data as $group)
		{
			foreach($group as $row) {
				$newrow = new stdClass();
				$newrow->key = $row->pageday_calendar___key_raw;
				$newrow->id = $row->pageday_calendar___id_raw;

				$newrow->productIdentifier = $row->pageday_calendar___productIdentifier_raw;
				$newrow->thumbLink = (string)$row->pageday_calendar___thumbLink;
				$newrow->previewLink =@(string)$row->pageday_calendar_preview_images___image;
				$newrow->free = @(int)$row->pageday_calendar___free_raw;
				$newrow->title = @(string)$row->pageday_calendar___displayName_raw;
				$newrow->description = @(string)$row->pageday_calendar___description_raw;
				$newrow->language = @(string)$row->pageday_calendar___language;
				$return[] = $newrow;
			}

		}
		$model->_data = $return;
	}

	/**
	 * once authenticated
	 * get a list of days for the calendars
	 * @param unknown_type $model
	 * @return unknown_type
	 */

	function getCalendarDays( &$model )
	{
		$return = new stdClass();
		if (!$this->validateRequest()) {
			$return->error = 'invalid request';
		} else {
			$this->filterKeys( $model->_data);
			$this->format( $return, $model);
		}
		$model->_data = $return;
	}

	/**
	 * reformat the data for the json output
	 * @param $return
	 * @param $model
	 * @return unknown_type
	 */

	function format( &$return, $model )
	{
		$remove = array('slug', '__pk_val', '_cursor', '_total', '_groupId', 'fabrik_delete', 'fabrik_edit_url',
			'fabrik_view_url');

		$last = JRequest::getInt('last');
		$return->calendars = array();

		$tablename = $model->getTable()->db_table_name;
		foreach($model->_data as &$group) {
			reset($group);
			$thisCal = new stdClass();
			$thisCal->days = array();
			if(!is_object(current($group))) {
				continue;
			}
			$first = clone(current($group));

			$days = array();
			foreach ($group as &$row) {
				$thisdate =& JFactory::getDate($row->pageday_day___date);
				//calendar days have already been requested
				//echo $thisdate->toMySQL() . "< $last <br>";
				if ($thisdate->toUnix() < $last) {
					continue;
				}
				$row->imageData = $row->pageday_day___imageData;
				foreach($row as $k => $v) {
					if(in_array($k, $remove)) {
						unset($row->$k);
					} else {
						//remove any top level stuff
						if (strstr($k, $tablename)) {
							unset($row->$k);
						}else{

							if (substr($k, -4) == '_raw' && $k != 'pageday_day___imageData_raw') {
								$k2 = FabrikString::rtrimword($k, "_raw");
								$k3 = array_pop(explode("___", $k2));
								$row->$k3 = $row->$k;
							if(is_null($row->$k3)) { $row->$k3 = ''; }
								unset($row->$k);
								unset($row->$k2);
							}

						}
					}
				}
				unset($row->pageday_day___imageData_raw);
				unset($row->pageday_day___imageData);
				if(is_null($row->imageData)) {
					$row->imageData = '';
				}
				$row->date = $thisdate->toUnix();
				$row->id = (int)$row->id;
				$thisCal->days[] = $row;
			}
			$thisCal->thumbLink = $first->pageday_calendar___thumbLink;
			if(is_null($thisCal->thumbLink)) { $thisCal->thumbLink = '';}
			foreach ($first as $k => $v) {
				if(strstr($k, $tablename)) {
					$k = array_pop(explode("___", $k));
					if (substr($k, -4) == '_raw' && $k != 'thumbLink_raw') {
						$k2 = FabrikString::rtrimword($k, "_raw");
						if(is_null($v)) { $v = ''; }
						$thisCal->$k2 = $v;
					}
				}
			}

			$thisCal->id = (int)$thisCal->id;
			$return->calendars[] = ($thisCal);
		}
	}

	/**
	 * filter the calendar keys on
	 * @param unknown_type $data
	 * @return unknown_type
	 */
	private function filterKeys(&$data)
	{

		$keys = explode(",", JRequest::getVar('keys'));
		foreach($data as  $k => &$group) {
			$found = false;
			foreach($group as $row) {
				if (in_array($row->pageday_calendar___key_raw, $keys)) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				unset($data[$k]);
			}
		}
	}

	/**
	 * check sum sha1 test
	 * @return bool ok or not
	 */
	private function validateRequest()
	{
		$d = JFactory::getDate(JRequest::getVar('last'));
		$k = sha1(JRequest::getVar('keys') . JRequest::getVar('last') . $this->salt);
		if(JRequest::getVar('test') == 1) {
			return true;
		}
		return $k === JRequest::getVar('verify');
	}

}


?>