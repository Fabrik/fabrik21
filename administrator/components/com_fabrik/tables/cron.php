<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */


// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * @package		Joomla
 * @subpackage	Fabrik
 */
class TableCron extends JTable
{
	/** @var int Primary key */
	var $id = null;

	/** @var string name */
	var $label = null;

	/** @var string "every $frequency $unit at $time - e.g. every 1 days at 12:00" */
	var $frequency = 1;

	/** @var string "every $frequency $unit at $time - e.g. every 1 days at 12:00" */
	var $unit = null;

	/** @var date created **/
	var $created = null;
	 
	/** @var int id of creator */
	var $created_by = null;
	 
	/** @var string creator alias */
	var $created_by_alias = null;
	 
	/** @var date modified */
	var $modified = null;
	 
	/** @var int id of modifier */
	var $modified_by = null;
	 
	/** @var int checked out */
	var $checked_out = null;
	 
	/** @var date checked out */
	var $checked_out_time = null;
	 
	/** @var int state */
	var $state = 1;
	
	/** @var string plugin */
	var $plugin = null;
	 
	/** @var timedate the last time the cron was run */
	var $lastrun = null;
	
	/** $var string group attribs */
	var $attribs = null;

	/*
	 *
	 */

	function __construct(&$_db )
	{
		parent::__construct('#__fabrik_cron', 'id', $_db);
	}

	/**
	 * overloaded check function
	 */

	function check() {

		return true;
	}

}
?>