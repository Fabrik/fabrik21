<?php

/**
* Determines if a row is deleteable
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

class FabrikModelCandeleterow extends FabrikModelTablePlugin {

	var $_counter = null;

	function canSelectRows()
	{
		return false;
	}

	function onCanDelete($params, $tableModel, $row)
	{
		// If $row is null, we were called from the table's canDelete() in a per-table rather than per-row context,
		// and we don't have an opinion on per-table delete permissions, so just return true.
		if (is_null($row) || is_null($row[0]))
		{
			return true;
		}
		if (is_array($row[0]))
		{
			$data = JArrayHelper::toObject($row[0]);
		}
		else
		{
			$data = $row[0];
		}
		$field = $params->get('candeleterow_field');
		$field = FabrikString::safeColnameToArrayKey($field);
		// $$$ rob if no can delete  field selected in admin return true
		// If they provided some PHP to eval, we ignore the other settings and just run their code
		$candeleterow_eval = $params->get('candeleterow_eval', '');
		if (trim($field) == '' && trim($candeleterow_eval) == '') {
			return true;
		}
		if (!empty($candeleterow_eval)) {
			$w = new FabrikWorker();
			$data = JArrayHelper::fromObject($data);
			$candeleterow_eval = $w->parseMessageForPlaceHolder($candeleterow_eval, $data);
			$candeleterow_eval = @eval($candeleterow_eval);
			FabrikWorker::logEval($candeleterow_eval, 'Caught exception on eval in can delete row : %s');
			return $candeleterow_eval;
		} else {
			// No PHP given, so just do a simple match on the specified element and value settigns.
			if ($params->get('candeleterow_useraw', '0') == '1') {
				$field .= '_raw';
			}
			$value = $params->get('candeleterow_value');
			return $data->$field == $value;
		}
	}
}