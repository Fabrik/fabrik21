<?php

/**
* Determines if a row can be selected
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

class FabrikModelCanselectrow extends FabrikModelTablePlugin {

	var $_counter = null;

	function canSelectRows()
	{
		return false;
	}

	function onCanSelectRow($params, $tableModel, $row)
	{
		// If $row is null, we were called from the table's canEdit() in a per-table rather than per-row context,
		// and we don't have an opinion on per-table edit permissions, so just return true.
		if (!is_object($row[0])) {
			return true;
		}
		$field = $params->get('canselectrow_field');
		$field = FabrikString::safeColnameToArrayKey($field);
		// $$$ rob if no can edit field selected in admin return true
		if (trim($field) == '') {
			return true;
		}
		$value = $params->get('canselectrow_value');
		return $row[0]->$field == $value;
	}

	function onCanEdit($params, $tableModel, $row)
	{
		return $this->onCanSelectRow($params, $tableModel, $row);
	}
}