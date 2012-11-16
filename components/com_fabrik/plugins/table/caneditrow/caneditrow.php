<?php

/**
* Determines if a row is editable
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

class FabrikModelCaneditrow extends FabrikModelTablePlugin {

	var $_counter = null;

	function canSelectRows()
	{
		return false;
	}

	function onCanEdit($params, $tableModel, $row)
	{
	// If $row is null, we were called from the table's canEdit() in a per-table rather than per-row context,
		// and we don't have an opinion on per-table edit permissions, so just return true.
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
		//$field = str_replace('.', '___', $params->get('caneditrow_field'));
		$field = $params->get('caneditrow_field');
		$field = FabrikString::safeColnameToArrayKey($field);
		// $$$ rob if no can edit field selected in admin return true
		if (trim($field) == '') {
			return true;
		}
		// If they provided some PHP to eval, we ignore the other settings and just run their code
		$caneditrow_eval = $params->get('caneditrow_eval', '');
		if (!empty($caneditrow_eval)) {
			$w = new FabrikWorker();
			$data = JArrayHelper::fromObject($data);
			$caneditrow_eval = $w->parseMessageForPlaceHolder($caneditrow_eval, $data);
			$caneditrow_eval = @eval($caneditrow_eval);
			FabrikWorker::logEval($caneditrow_eval, 'Caught exception on eval in can edit row : %s');
			return $caneditrow_eval;
		} else {
			// No PHP given, so just do a simple match on the specified element and value settigns.
			if ($params->get('caneditrow_useraw', '0') == '1') {
				$field .= '_raw';
			}
			$value = $params->get('caneditrow_value');
			return $data->$field == $value;
		}
	}
}