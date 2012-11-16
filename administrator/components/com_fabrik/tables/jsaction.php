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
class TableJsaction extends JTable
{
	/** @var int Primary key */
	var $id = null;

	/** @var int element id */
	var $element_id = null;

	/** @var string action */
	var $action = null;

	/** @var code */
	var $code = null;

	/** @var string attribs **/
	var $attribs = null;

	/*
	 *
	 */

	function __construct(&$_db )
	{
		parent::__construct('#__fabrik_jsactions', 'id', $_db);
	}

}
?>