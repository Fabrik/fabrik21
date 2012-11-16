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
class TableLog extends JTable
{
	/** @var int Primary key */
	var $id = null;

	/** @var timestamp name */
	var $timedate_created = null;

	/** @var string url that generated the log message */
	var $referring_url = 1;

	/** @var string message type recordadded/recorddeleted/elementchanged/ */
	var $message_type = null;

	/** @var string log message **/
	var $message = null;

	/*
	 *
	 */

	function __construct(&$_db )
	{
		parent::__construct('#__fabrik_log', 'id', $_db);
	}


}
?>